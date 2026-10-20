<?php
/**
 * A TrueType font inside the portal's PDFs, for what the built-in PDF fonts
 * cannot write: names in Hindi (Devanagari) and the rupee sign ₹.
 *
 * The font files ship with the plugin in assets/fonts/. This class reads one,
 * turns a text into glyphs the way a browser does (OpenType GSUB and GPOS with
 * the Indic shaping rules, so conjuncts, half forms, the reph and the vowel
 * signs come out right) and gives the PDF writer a copy of the font that holds
 * only the glyphs a document used, so a receipt stays small. Nothing has to be
 * installed on the server.
 *
 * The shaping follows HarfBuzz, the engine of Chrome, Firefox and Android, for
 * the Devanagari script.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAGS_ERP_Font {
	/** Glyphs of a syllable that a masked Indic feature may change. */
	const RPHF = 1;
	const HALF = 2;
	const BLWF = 4;
	const ABVF = 8;
	const PSTF = 16;
	const PREF = 32;
	const INIT = 64;

	/** Places inside a syllable, in the order the characters are sorted. */
	const POS_RA_TO_BECOME_REPH = 1;
	const POS_PRE_M             = 2;
	const POS_PRE_C             = 3;
	const POS_BASE_C            = 4;
	const POS_AFTER_MAIN        = 5;
	const POS_BELOW_C           = 8;
	const POS_AFTER_SUB         = 9;
	const POS_POST_C            = 11;
	const POS_AFTER_POST        = 12;
	const POS_SMVD              = 13;
	const POS_END               = 14;

	/** Letters with a nukta that may be written as the letter followed by U+093C. */
	const NUKTA_LETTERS = array( 0x0929 => 0x0928, 0x0931 => 0x0930, 0x0934 => 0x0933, 0x0958 => 0x0915, 0x0959 => 0x0916, 0x095A => 0x0917, 0x095B => 0x091C, 0x095C => 0x0921, 0x095D => 0x0922, 0x095E => 0x092B, 0x095F => 0x092F );

	private $data;
	private $tables = array();
	private $num_glyphs;
	private $num_hmetrics;
	private $loca_long;
	private $cmap = array();
	private $glyph_class = array();
	private $attach_class = array();
	private $mark_sets = array();
	private $features = array();
	private $lookups = array();
	private $coverages = array();
	private $classdefs = array();
	private $shaped = array();
	private $virama = 0;
	/** Whether ZWJ and ZWNJ are passed over while matching: array( input, context ), each array( zwj, zwnj ). */
	private $ignore = array( array( false, false ), array( true, false ) );

	public $name;
	public $units_per_em;
	public $ascent;
	public $descent;
	public $cap_height;
	public $bbox;

	/** The font at $path, read once per request, or null when it is missing or not a TrueType font. */
	public static function load( $path ) {
		static $fonts = array();
		if ( ! array_key_exists( $path, $fonts ) ) {
			$fonts[ $path ] = null;
			$data = is_string( $path ) && is_readable( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( false !== $data ) {
				try {
					$fonts[ $path ] = new self( $data, preg_replace( '/[^A-Za-z0-9-]+/', '', basename( $path, '.ttf' ) ) );
				} catch ( Exception $e ) {
					$fonts[ $path ] = null;
				}
			}
		}
		return $fonts[ $path ];
	}

	private function __construct( $data, $name ) {
		$this->data = $data;
		$this->name = '' !== $name ? $name : 'Font';
		if ( strlen( $data ) < 12 || ! in_array( $this->u32( 0 ), array( 0x00010000, 0x74727565 ), true ) ) { throw new Exception( 'Not a TrueType font.' ); }
		for ( $i = 0, $n = $this->u16( 4 ); $i < $n; $i++ ) {
			$record = 12 + 16 * $i;
			$offset = $this->u32( $record + 8 );
			$length = $this->u32( $record + 12 );
			if ( $offset + $length > strlen( $data ) ) { throw new Exception( 'Broken font file.' ); }
			$this->tables[ substr( $data, $record, 4 ) ] = array( $offset, $length );
		}
		foreach ( array( 'head', 'hhea', 'hmtx', 'maxp', 'cmap', 'loca', 'glyf' ) as $tag ) {
			if ( ! isset( $this->tables[ $tag ] ) ) { throw new Exception( 'The font has no ' . $tag . ' table.' ); }
		}
		$head               = $this->tables['head'][0];
		$this->units_per_em = $this->u16( $head + 18 ) ?: 1000;
		$this->bbox         = array( $this->s16( $head + 36 ), $this->s16( $head + 38 ), $this->s16( $head + 40 ), $this->s16( $head + 42 ) );
		$this->loca_long    = 1 === $this->s16( $head + 50 );
		$this->ascent       = $this->s16( $this->tables['hhea'][0] + 4 );
		$this->descent      = $this->s16( $this->tables['hhea'][0] + 6 );
		$this->num_hmetrics = max( 1, $this->u16( $this->tables['hhea'][0] + 34 ) );
		$this->num_glyphs   = $this->u16( $this->tables['maxp'][0] + 4 );
		$this->cap_height   = isset( $this->tables['OS/2'] ) && $this->u16( $this->tables['OS/2'][0] ) >= 2 ? $this->s16( $this->tables['OS/2'][0] + 88 ) : $this->ascent;
		$this->read_cmap();
		$this->read_gdef();
		$this->virama = $this->cmap[0x094D] ?? 0;
	}

	/* ---------- Reading the file ---------- */

	private function u16( $at ) {
		return ( ord( $this->data[ $at ] ) << 8 ) | ord( $this->data[ $at + 1 ] );
	}

	private function s16( $at ) {
		$value = $this->u16( $at );
		return $value >= 0x8000 ? $value - 0x10000 : $value;
	}

	private function u32( $at ) {
		return ( ord( $this->data[ $at ] ) << 24 ) | ( ord( $this->data[ $at + 1 ] ) << 16 ) | ( ord( $this->data[ $at + 2 ] ) << 8 ) | ord( $this->data[ $at + 3 ] );
	}

	private function read_cmap() {
		$base = $this->tables['cmap'][0];
		$best = 0;
		$rank = 0;
		for ( $i = 0, $n = $this->u16( $base + 2 ); $i < $n; $i++ ) {
			$platform = $this->u16( $base + 4 + 8 * $i );
			$encoding = $this->u16( $base + 6 + 8 * $i );
			$offset   = $base + $this->u32( $base + 8 + 8 * $i );
			$format   = $this->u16( $offset );
			$score    = 0;
			if ( 12 === $format && ( ( 3 === $platform && 10 === $encoding ) || 0 === $platform ) ) { $score = 4; }
			if ( 4 === $format && 3 === $platform && 1 === $encoding ) { $score = 3; }
			if ( 4 === $format && 0 === $platform ) { $score = 2; }
			if ( $score > $rank ) { $rank = $score; $best = $offset; }
		}
		if ( ! $best ) { throw new Exception( 'The font has no Unicode character map.' ); }

		if ( 12 === $this->u16( $best ) ) {
			for ( $i = 0, $n = $this->u32( $best + 12 ); $i < $n; $i++ ) {
				$group = $best + 16 + 12 * $i;
				$first = $this->u32( $group );
				$last  = min( $this->u32( $group + 4 ), $first + 0xFFFF );
				$glyph = $this->u32( $group + 8 );
				for ( $c = $first; $c <= $last; $c++ ) { $this->cmap[ $c ] = $glyph + $c - $first; }
			}
			return;
		}
		$segments = $this->u16( $best + 6 ) / 2;
		$ends     = $best + 14;
		$starts   = $ends + 2 * $segments + 2;
		$deltas   = $starts + 2 * $segments;
		$ranges   = $deltas + 2 * $segments;
		for ( $s = 0; $s < $segments; $s++ ) {
			$start = $this->u16( $starts + 2 * $s );
			$end   = $this->u16( $ends + 2 * $s );
			$delta = $this->u16( $deltas + 2 * $s );
			$range = $this->u16( $ranges + 2 * $s );
			if ( 0xFFFF === $start ) { break; }
			for ( $c = $start; $c <= $end; $c++ ) {
				$glyph = $range ? $this->u16( $ranges + 2 * $s + $range + 2 * ( $c - $start ) ) : $c;
				if ( $range && 0 === $glyph ) { continue; }
				$glyph = ( $glyph + $delta ) & 0xFFFF;
				if ( $glyph ) { $this->cmap[ $c ] = $glyph; }
			}
		}
	}

	private function read_gdef() {
		if ( ! isset( $this->tables['GDEF'] ) ) { return; }
		$base = $this->tables['GDEF'][0];
		if ( $this->u16( $base + 4 ) ) { $this->glyph_class = $this->classdef( $base + $this->u16( $base + 4 ) ); }
		if ( $this->u16( $base + 10 ) ) { $this->attach_class = $this->classdef( $base + $this->u16( $base + 10 ) ); }
		if ( $this->u32( $base ) >= 0x00010002 && $this->u16( $base + 12 ) ) {
			$sets = $base + $this->u16( $base + 12 );
			for ( $i = 0, $n = $this->u16( $sets + 2 ); $i < $n; $i++ ) { $this->mark_sets[ $i ] = $this->coverage( $sets + $this->u32( $sets + 4 + 4 * $i ) ); }
		}
	}

	/** Coverage table: glyph => coverage index. */
	private function coverage( $at ) {
		if ( isset( $this->coverages[ $at ] ) ) { return $this->coverages[ $at ]; }
		$map = array();
		if ( 1 === $this->u16( $at ) ) {
			for ( $i = 0, $n = $this->u16( $at + 2 ); $i < $n; $i++ ) { $map[ $this->u16( $at + 4 + 2 * $i ) ] = $i; }
		} elseif ( 2 === $this->u16( $at ) ) {
			for ( $i = 0, $n = $this->u16( $at + 2 ); $i < $n; $i++ ) {
				$range = $at + 4 + 6 * $i;
				for ( $g = $this->u16( $range ), $last = $this->u16( $range + 2 ), $index = $this->u16( $range + 4 ); $g <= $last; $g++, $index++ ) { $map[ $g ] = $index; }
			}
		}
		return $this->coverages[ $at ] = $map;
	}

	/** Class definition table: glyph => class (glyphs not listed are class 0). */
	private function classdef( $at ) {
		if ( isset( $this->classdefs[ $at ] ) ) { return $this->classdefs[ $at ]; }
		$map = array();
		if ( 1 === $this->u16( $at ) ) {
			for ( $i = 0, $first = $this->u16( $at + 2 ), $n = $this->u16( $at + 4 ); $i < $n; $i++ ) { $map[ $first + $i ] = $this->u16( $at + 6 + 2 * $i ); }
		} elseif ( 2 === $this->u16( $at ) ) {
			for ( $i = 0, $n = $this->u16( $at + 2 ); $i < $n; $i++ ) {
				$range = $at + 4 + 6 * $i;
				for ( $g = $this->u16( $range ), $last = $this->u16( $range + 2 ), $class = $this->u16( $range + 4 ); $g <= $last; $g++ ) { $map[ $g ] = $class; }
			}
		}
		return $this->classdefs[ $at ] = $map;
	}

	/** Horizontal advance of a glyph in font units. */
	public function advance( $glyph ) {
		$index = min( (int) $glyph, $this->num_hmetrics - 1 );
		return $this->u16( $this->tables['hmtx'][0] + 4 * $index );
	}

	/** True when the font has a glyph for this character (or for the letter and nukta it stands for). */
	public function has_char( $code ) {
		if ( isset( $this->cmap[ $code ] ) ) { return true; }
		if ( 0x200C === $code || 0x200D === $code ) { return true; }
		return isset( self::NUKTA_LETTERS[ $code ], $this->cmap[ self::NUKTA_LETTERS[ $code ] ], $this->cmap[0x093C] );
	}

	/* ---------- Features and lookups ---------- */

	/** feature tag => lookup indices, for the first of the scripts the font has (default language). */
	private function features( $table ) {
		if ( isset( $this->features[ $table ] ) ) { return $this->features[ $table ]; }
		$this->features[ $table ] = array();
		if ( ! isset( $this->tables[ $table ] ) ) { return array(); }
		$base    = $this->tables[ $table ][0];
		$scripts = $base + $this->u16( $base + 4 );
		$list    = $base + $this->u16( $base + 6 );
		$found   = array();
		for ( $i = 0, $n = $this->u16( $scripts ); $i < $n; $i++ ) { $found[ substr( $this->data, $scripts + 2 + 6 * $i, 4 ) ] = $scripts + $this->u16( $scripts + 6 + 6 * $i ); }
		$script = $found['dev2'] ?? $found['deva'] ?? $found['DFLT'] ?? $found['latn'] ?? 0;
		if ( ! $script || ! $this->u16( $script ) ) { return array(); }
		$lang    = $script + $this->u16( $script );
		$indices = array();
		if ( 0xFFFF !== $this->u16( $lang + 2 ) ) { $indices[] = $this->u16( $lang + 2 ); }
		for ( $i = 0, $n = $this->u16( $lang + 4 ); $i < $n; $i++ ) { $indices[] = $this->u16( $lang + 6 + 2 * $i ); }
		foreach ( $indices as $index ) {
			$tag     = substr( $this->data, $list + 2 + 6 * $index, 4 );
			$feature = $list + $this->u16( $list + 6 + 6 * $index );
			for ( $i = 0, $n = $this->u16( $feature + 2 ); $i < $n; $i++ ) { $this->features[ $table ][ $tag ][] = $this->u16( $feature + 4 + 2 * $i ); }
		}
		return $this->features[ $table ];
	}

	/** One lookup: type, flag, mark filtering set and its subtables as list of array( type, offset ). */
	private function lookup( $table, $index ) {
		if ( isset( $this->lookups[ $table ][ $index ] ) ) { return $this->lookups[ $table ][ $index ]; }
		$base   = $this->tables[ $table ][0];
		$list   = $base + $this->u16( $base + 8 );
		$at     = $list + $this->u16( $list + 2 + 2 * $index );
		$type   = $this->u16( $at );
		$flag   = $this->u16( $at + 2 );
		$count  = $this->u16( $at + 4 );
		$lookup = array( 'type' => $type, 'flag' => $flag, 'set' => $flag & 0x10 ? $this->u16( $at + 6 + 2 * $count ) : -1, 'subs' => array() );
		$extension = 'GSUB' === $table ? 7 : 9;
		for ( $i = 0; $i < $count; $i++ ) {
			$sub = $at + $this->u16( $at + 6 + 2 * $i );
			if ( $type === $extension ) {
				$lookup['subs'][] = array( $this->u16( $sub + 2 ), $sub + $this->u32( $sub + 4 ) );
			} else {
				$lookup['subs'][] = array( $type, $sub );
			}
		}
		return $this->lookups[ $table ][ $index ] = $lookup;
	}

	/** Should a lookup with these flags pass over this glyph? */
	private function skips( $glyph, $lookup ) {
		$class = $this->glyph_class[ $glyph ] ?? 0;
		$flag  = $lookup['flag'];
		if ( 1 === $class ) { return (bool) ( $flag & 2 ); }
		if ( 2 === $class ) { return (bool) ( $flag & 4 ); }
		if ( 3 !== $class ) { return false; }
		if ( $flag & 8 ) { return true; }
		if ( $flag & 0x10 ) { return ! isset( $this->mark_sets[ $lookup['set'] ][ $glyph ] ); }
		if ( $flag & 0xFF00 ) { return ( $this->attach_class[ $glyph ] ?? 0 ) !== ( $flag >> 8 ); }
		return false;
	}

	/** Is this a joiner that the current matching passes over? */
	private function ignorable( $item, $context ) {
		list( $zwj, $zwnj ) = $this->ignore[ $context ? 1 : 0 ];
		return ( $zwj && 0x200D === $item['u'] ) || ( $zwnj && 0x200C === $item['u'] );
	}

	private function next_index( $items, $from, $lookup, $context = false ) {
		for ( $k = $from + 1, $n = count( $items ); $k < $n; $k++ ) {
			if ( ! $this->skips( $items[ $k ]['g'], $lookup ) && ! $this->ignorable( $items[ $k ], $context ) ) { return $k; }
		}
		return -1;
	}

	private function prev_index( $items, $from, $lookup, $context = false ) {
		for ( $k = $from - 1; $k >= 0; $k-- ) {
			if ( ! $this->skips( $items[ $k ]['g'], $lookup ) && ! $this->ignorable( $items[ $k ], $context ) ) { return $k; }
		}
		return -1;
	}

	/* ---------- Shaping ---------- */

	/**
	 * The glyphs of a text: list of array( 'g' => glyph, 'xa' => advance, 'xo', 'yo' => offsets, 't' => text ),
	 * in font units. Drawing glyph i at (pen + xo, baseline + yo) and moving the pen by xa sets the text.
	 */
	public function shape( $text ) {
		if ( isset( $this->shaped[ $text ] ) ) { return $this->shaped[ $text ]; }
		$items = array();
		foreach ( $this->syllables( $this->chars( $text ) ) as $syllable ) {
			foreach ( $this->shape_syllable( $syllable[0], $syllable[1] ) as $item ) { $items[] = $item; }
		}
		foreach ( $items as $i => $item ) { $items[ $i ] += array( 'xa' => $this->advance( $item['g'] ), 'xo' => 0, 'yo' => 0, 'att' => -1 ); }
		$this->apply_gpos( $items );

		$glyphs = array();
		foreach ( $items as $i => $item ) {
			if ( $item['att'] >= 0 ) {
				$base = $items[ $item['att'] ];
				$item['xo'] += $base['xo'];
				$item['yo'] += $base['yo'];
				for ( $k = $item['att']; $k < $i; $k++ ) { $item['xo'] -= $items[ $k ]['xa']; }
				$items[ $i ] = $item;
			}
			// Joiners only steer the shaping; they are not drawn.
			if ( 0x200C === $item['u'] || 0x200D === $item['u'] ) { $item['xa'] = 0; $item['hide'] = true; }
			$glyphs[] = array( 'g' => $item['g'], 'xa' => $item['xa'], 'xo' => $item['xo'], 'yo' => $item['yo'], 't' => $item['t'], 'hide' => ! empty( $item['hide'] ) );
		}
		if ( count( $this->shaped ) > 500 ) { $this->shaped = array(); }
		return $this->shaped[ $text ] = $glyphs;
	}

	/** Width of a text in font units. */
	public function width( $text ) {
		$width = 0;
		foreach ( $this->shape( $text ) as $glyph ) { $width += $glyph['xa']; }
		return $width;
	}

	/** Shaping class of a character: C consonant, R Ra, V vowel, N nukta, H virama, M vowel sign, S modifier, A accent, P placeholder, D dotted circle, J/K joiners, Y symbol, X other. */
	private static function category( $u ) {
		if ( 0x0930 === $u ) { return 'R'; }
		if ( ( $u >= 0x0915 && $u <= 0x0939 ) || ( $u >= 0x0958 && $u <= 0x095F ) || ( $u >= 0x0978 && $u <= 0x097F ) ) { return 'C'; }
		if ( ( $u >= 0x0904 && $u <= 0x0914 ) || 0x0960 === $u || 0x0961 === $u || ( $u >= 0x0972 && $u <= 0x0977 ) ) { return 'V'; }
		if ( 0x093C === $u ) { return 'N'; }
		if ( 0x094D === $u ) { return 'H'; }
		if ( 0x093A === $u || 0x093B === $u || ( $u >= 0x093E && $u <= 0x094C ) || 0x094E === $u || 0x094F === $u || ( $u >= 0x0955 && $u <= 0x0957 ) || 0x0962 === $u || 0x0963 === $u ) { return 'M'; }
		if ( $u >= 0x0900 && $u <= 0x0903 ) { return 'S'; }
		if ( $u >= 0x0951 && $u <= 0x0954 ) { return 'A'; }
		if ( ( $u >= 0x0966 && $u <= 0x096F ) || 0x00A0 === $u ) { return 'P'; }
		if ( 0x25CC === $u ) { return 'D'; }
		if ( 0x200D === $u ) { return 'J'; }
		if ( 0x200C === $u ) { return 'K'; }
		if ( 0x093D === $u || 0x0950 === $u ) { return 'Y'; }
		return 'X';
	}

	private static function position( $u, $category ) {
		if ( in_array( $category, array( 'C', 'R', 'V', 'P', 'D' ), true ) ) { return self::POS_BASE_C; }
		if ( 'M' === $category ) { return 0x093F === $u || 0x094E === $u ? self::POS_PRE_M : self::POS_AFTER_SUB; }
		if ( 'S' === $category || 'A' === $category ) { return self::POS_SMVD; }
		return self::POS_END;
	}

	private function item( $u, $text ) {
		$category = self::category( $u );
		return array( 'g' => $this->cmap[ $u ] ?? 0, 'u' => $u, 'c' => $category, 'p' => self::position( $u, $category ), 'm' => 0, 'lig' => false, 'mul' => false, 't' => $text );
	}

	/**
	 * The characters of a text as glyph items, normalised as HarfBuzz does: a
	 * nukta letter is written as letter + nukta, the signs after a letter are
	 * put in canonical order (nukta before virama), and न ऱ ळ + nukta become
	 * ऩ ऱ ऴ again when the font has them.
	 */
	private function chars( $text ) {
		$items = array();
		foreach ( preg_split( '//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
			$u = mb_ord( $char, 'UTF-8' );
			if ( isset( self::NUKTA_LETTERS[ $u ], $this->cmap[ self::NUKTA_LETTERS[ $u ] ], $this->cmap[0x093C] ) ) {
				$items[] = $this->item( self::NUKTA_LETTERS[ $u ], $char );
				$items[] = $this->item( 0x093C, '' );
			} else {
				$items[] = $this->item( $u, $char );
			}
		}

		$classes = array( 0x093C => 7, 0x094D => 9, 0x0951 => 230, 0x0952 => 220, 0x0953 => 230, 0x0954 => 230 );
		for ( $i = 1, $n = count( $items ); $i < $n; $i++ ) {
			for ( $j = $i; $j > 0 && isset( $classes[ $items[ $j ]['u'] ], $classes[ $items[ $j - 1 ]['u'] ] ) && $classes[ $items[ $j - 1 ]['u'] ] > $classes[ $items[ $j ]['u'] ]; $j-- ) {
				$swap            = $items[ $j ];
				$items[ $j ]     = $items[ $j - 1 ];
				$items[ $j - 1 ] = $swap;
			}
		}

		$composed = array_flip( array_slice( self::NUKTA_LETTERS, 0, 3, true ) );
		for ( $i = count( $items ) - 1; $i > 0; $i-- ) {
			$letter = $composed[ $items[ $i - 1 ]['u'] ] ?? 0;
			if ( 0x093C === $items[ $i ]['u'] && $letter && isset( $this->cmap[ $letter ] ) ) {
				$items[ $i - 1 ] = $this->item( $letter, $items[ $i - 1 ]['t'] . $items[ $i ]['t'] );
				array_splice( $items, $i, 1 );
			}
		}
		return $items;
	}

	/** Split the items into syllables (as the HarfBuzz Indic machine does): list of array( kind, items ). */
	private function syllables( $items ) {
		static $patterns = null;
		if ( null === $patterns ) {
			$cn    = '[CR]J?(?:NN?)?';
			$tail  = '(?:[JK]?H(?:JN?)?' . $cn . ')*(?:HK|[JK]?H(?:JN?)?|(?:[JK]*MN?H?)*)(?:[JK]?SS?K?)?A*';
			$patterns = array(
				'consonant'  => '/\G' . $cn . $tail . '/',
				'vowel'      => '/\G(?:RH)?V(?:NN?)?(?:J|' . $tail . ')/',
				'standalone' => '/\G(?:P|(?:RH)?D)(?:NN?)?' . $tail . '/',
				'symbol'     => '/\GYN?(?:[JK]?SS?K?)?A*/',
				'broken'     => '/\G(?:RH)?(?:NN?)?' . $tail . '/',
			);
		}
		$string    = implode( '', array_column( $items, 'c' ) );
		$syllables = array();
		for ( $at = 0, $n = strlen( $string ); $at < $n; ) {
			$kind   = 'other';
			$length = 1;
			$best   = 0;
			foreach ( $patterns as $name => $pattern ) {
				if ( preg_match( $pattern, $string, $match, 0, $at ) && strlen( $match[0] ) > $best ) {
					$best = strlen( $match[0] );
					$kind = $name;
				}
			}
			if ( $best ) { $length = $best; } else { $kind = 'other'; }
			$part = array_slice( $items, $at, $length );
			// A vowel sign or virama with nothing to sit on is shown on a dotted circle, as browsers do.
			if ( 'broken' === $kind && isset( $this->cmap[0x25CC] ) ) { array_unshift( $part, $this->item( 0x25CC, '' ) ); }
			$syllables[] = array( $kind, $part );
			$at += $length;
		}
		return $syllables;
	}

	private function shape_syllable( $kind, $items ) {
		$this->gsub_stage( $items, array( 'locl', 'ccmp' ) );
		if ( in_array( $kind, array( 'consonant', 'vowel', 'standalone', 'broken' ), true ) ) { $this->initial_reorder( $items ); }
		foreach ( array( 'nukt', 'akhn', 'rphf', 'rkrf', 'pref', 'blwf', 'abvf', 'half', 'pstf', 'vatu', 'cjct' ) as $tag ) { $this->gsub_stage( $items, array( $tag ) ); }
		$this->final_reorder( $items );
		$this->gsub_stage( $items, array( 'init', 'pres', 'abvs', 'blws', 'psts', 'haln', 'calt', 'clig', 'liga', 'rclt' ) );
		return $items;
	}

	/** Item test used by the reordering: a ligated glyph is none of these any more. */
	private static function is( $item, $categories ) {
		return ! $item['lig'] && false !== strpos( $categories, $item['c'] );
	}

	/** Would the lookups of a feature change exactly this glyph sequence? */
	private function would_substitute( $tag, $glyphs ) {
		$this->ignore = array( array( false, false ), array( true, false ) );
		foreach ( $this->features( 'GSUB' )[ $tag ] ?? array() as $index ) {
			$items = array();
			foreach ( $glyphs as $glyph ) { $items[] = array( 'g' => $glyph, 'u' => 0, 'c' => 'X', 'p' => 0, 'm' => 0, 'lig' => false, 'mul' => false, 't' => '' ); }
			$lookup = $this->lookup( 'GSUB', $index );
			foreach ( $lookup['subs'] as $sub ) {
				if ( 4 === $sub[0] || 1 === $sub[0] || 2 === $sub[0] ) {
					$copy = $items;
					if ( false !== $this->gsub_subtable( $sub[0], $sub[1], $lookup, $copy, 0, 0 ) && ( 4 !== $sub[0] || 1 === count( $copy ) ) && ( 4 === $sub[0] || 1 === count( $glyphs ) ) ) { return true; }
				}
			}
		}
		return false;
	}

	/** Where a consonant goes when it follows a virama: below the base (rakar), after it, or it is a base itself. */
	private function consonant_position( $glyph ) {
		foreach ( array( 'blwf' => self::POS_BELOW_C, 'vatu' => self::POS_BELOW_C, 'pstf' => self::POS_POST_C, 'pref' => self::POS_POST_C ) as $tag => $position ) {
			if ( $this->would_substitute( $tag, array( $this->virama, $glyph ) ) || $this->would_substitute( $tag, array( $glyph, $this->virama ) ) ) { return $position; }
		}
		return self::POS_BASE_C;
	}

	/** Find the base consonant, mark the reph and the pre-base and post-base glyphs, and sort the syllable. */
	private function initial_reorder( &$info ) {
		static $positions = array();
		$end = count( $info );
		foreach ( $info as $i => $item ) {
			if ( self::POS_BASE_C === $item['p'] ) {
				if ( ! isset( $positions[ $this->name ][ $item['g'] ] ) ) { $positions[ $this->name ][ $item['g'] ] = $this->consonant_position( $item['g'] ); }
				$info[ $i ]['p'] = $positions[ $this->name ][ $item['g'] ];
			}
		}

		$base      = $end;
		$has_reph  = false;
		$limit     = 0;
		if ( 3 <= $end && 'R' === $info[0]['c'] && 'H' === $info[1]['c'] && ! self::is( $info[2], 'JK' ) && $this->would_substitute( 'rphf', array( $info[0]['g'], $info[1]['g'] ) ) ) {
			$limit = 2;
			while ( $limit < $end && self::is( $info[ $limit ], 'JK' ) ) { $limit++; }
			$base     = 0;
			$has_reph = true;
		}
		$i          = $end;
		$seen_below = false;
		do {
			$i--;
			if ( self::is( $info[ $i ], 'CRVPD' ) ) {
				if ( self::POS_BELOW_C !== $info[ $i ]['p'] && ( self::POS_POST_C !== $info[ $i ]['p'] || $seen_below ) ) {
					$base = $i;
					break;
				}
				if ( self::POS_BELOW_C === $info[ $i ]['p'] ) { $seen_below = true; }
				$base = $i;
			} elseif ( 0 < $i && 'J' === $info[ $i ]['c'] && 'H' === $info[ $i - 1 ]['c'] ) {
				break;
			}
		} while ( $i > $limit );
		if ( $has_reph && 0 === $base && $limit - $base <= 2 ) { $has_reph = false; }

		for ( $i = 0; $i < $base; $i++ ) { $info[ $i ]['p'] = min( self::POS_PRE_C, $info[ $i ]['p'] ); }
		if ( $base < $end ) { $info[ $base ]['p'] = self::POS_BASE_C; }
		if ( $has_reph ) { $info[0]['p'] = self::POS_RA_TO_BECOME_REPH; }

		// Nukta, virama and joiners move with the character before them.
		$last_pos = 0;
		for ( $i = 0; $i < $end; $i++ ) {
			if ( false !== strpos( 'JKNH', $info[ $i ]['c'] ) ) {
				$info[ $i ]['p'] = $last_pos;
				if ( 'H' === $info[ $i ]['c'] && self::POS_PRE_M === $info[ $i ]['p'] ) {
					for ( $j = $i; $j > 0; $j-- ) {
						if ( self::POS_PRE_M !== $info[ $j - 1 ]['p'] ) { $info[ $i ]['p'] = $info[ $j - 1 ]['p']; break; }
					}
				}
			} elseif ( self::POS_SMVD !== $info[ $i ]['p'] ) {
				$last_pos = $info[ $i ]['p'];
			}
		}
		// A post-base consonant owns what stands between it and the consonant or vowel sign before it.
		$last = $base;
		for ( $i = $base + 1; $i < $end; $i++ ) {
			if ( self::is( $info[ $i ], 'CRVPD' ) ) {
				for ( $j = $last + 1; $j < $i; $j++ ) {
					if ( $info[ $j ]['p'] < self::POS_SMVD ) { $info[ $j ]['p'] = $info[ $i ]['p']; }
				}
				$last = $i;
			} elseif ( 'M' === $info[ $i ]['c'] ) {
				$last = $i;
			}
		}

		// Stable sort by position.
		foreach ( $info as $i => $item ) { $info[ $i ]['o'] = $i; }
		usort( $info, static function ( $a, $b ) { return $a['p'] <=> $b['p'] ?: $a['o'] <=> $b['o']; } );
		$first_left = $end;
		$last_left  = $end;
		$base       = $end;
		for ( $i = 0; $i < $end; $i++ ) {
			if ( self::POS_BASE_C === $info[ $i ]['p'] ) { $base = $i; break; }
			if ( self::POS_PRE_M === $info[ $i ]['p'] ) {
				if ( $end === $first_left ) { $first_left = $i; }
				$last_left = $i;
			}
		}
		if ( $first_left < $last_left ) {
			$info = self::reverse( $info, $first_left, $last_left );
			// Nuktas and the like turn back to follow their vowel sign.
			for ( $i = $first_left, $j = $first_left; $j <= $last_left; $j++ ) {
				if ( 'M' === $info[ $j ]['c'] ) {
					$info = self::reverse( $info, $i, $j );
					$i    = $j + 1;
				}
			}
		}

		// Which glyphs the masked features may change.
		for ( $i = 0; $i < $end && self::POS_RA_TO_BECOME_REPH === $info[ $i ]['p']; $i++ ) { $info[ $i ]['m'] |= self::RPHF; }
		for ( $i = 0; $i < $base; $i++ ) { $info[ $i ]['m'] |= self::HALF | self::BLWF; }
		for ( $i = $base + 1; $i < $end; $i++ ) { $info[ $i ]['m'] |= self::BLWF | self::ABVF | self::PSTF; }
		// A ZWNJ stops the half form of the consonant before it.
		for ( $i = 1; $i < $end; $i++ ) {
			if ( 'K' !== $info[ $i ]['c'] ) { continue; }
			$j = $i;
			do {
				$j--;
				$info[ $j ]['m'] &= ~self::HALF;
			} while ( $j > 0 && ! self::is( $info[ $j ], 'CRVPD' ) );
		}
	}

	private static function reverse( $items, $from, $to ) {
		return array_merge( array_slice( $items, 0, $from ), array_reverse( array_slice( $items, $from, $to - $from + 1 ) ), array_slice( $items, $to + 1 ) );
	}

	/** Put the pre-base vowel sign and the reph where they belong once the conjuncts are made. */
	private function final_reorder( &$info ) {
		$end = count( $info );
		if ( $end < 2 ) { return; }
		foreach ( $info as $i => $item ) {
			if ( $this->virama && $item['g'] === $this->virama && $item['lig'] && $item['mul'] ) {
				$info[ $i ]['c']   = 'H';
				$info[ $i ]['lig'] = false;
				$info[ $i ]['mul'] = false;
			}
		}

		for ( $base = 0; $base < $end; $base++ ) {
			if ( $info[ $base ]['p'] >= self::POS_BASE_C ) { break; }
		}
		if ( 0 < $base && $base < $end && $info[ $base ]['p'] > self::POS_BASE_C ) { $base--; }
		if ( $base === $end && 0 < $base && self::is( $info[ $base - 1 ], 'J' ) ) { $base--; }
		if ( $base < $end ) {
			while ( 0 < $base && self::is( $info[ $base ], 'NH' ) ) { $base--; }
		}

		// The vowel sign ि goes after the last half form, just before the main consonant.
		if ( 1 < $end && 0 < $base ) {
			$new_pos = $base === $end ? $base - 2 : $base - 1;
			while ( true ) {
				while ( $new_pos > 0 && ! self::is( $info[ $new_pos ], 'MH' ) ) { $new_pos--; }
				if ( self::is( $info[ $new_pos ], 'H' ) && self::POS_PRE_M !== $info[ $new_pos ]['p'] ) {
					if ( $new_pos + 1 < $end && 'J' === $info[ $new_pos + 1 ]['c'] && $new_pos > 0 ) {
						$new_pos--;
						continue;
					}
				} else {
					$new_pos = 0;
				}
				break;
			}
			if ( 0 < $new_pos && self::POS_PRE_M !== $info[ $new_pos ]['p'] ) {
				for ( $i = $new_pos; $i > 0; $i-- ) {
					if ( self::POS_PRE_M === $info[ $i - 1 ]['p'] ) {
						$old_pos = $i - 1;
						if ( $old_pos < $base && $base <= $new_pos ) { $base--; }
						$moved = $info[ $old_pos ];
						array_splice( $info, $old_pos, 1 );
						array_splice( $info, $new_pos, 0, array( $moved ) );
						$new_pos--;
					}
				}
			}
		}

		// The reph goes to the end of the syllable, before the syllable modifiers.
		if ( self::POS_RA_TO_BECOME_REPH === $info[0]['p'] && $info[0]['lig'] && ! $info[0]['mul'] ) {
			$new_reph_pos = 1;
			while ( $new_reph_pos < $base && ! self::is( $info[ $new_reph_pos ], 'H' ) ) { $new_reph_pos++; }
			if ( $new_reph_pos < $base && self::is( $info[ $new_reph_pos ], 'H' ) ) {
				if ( $new_reph_pos + 1 < $base && self::is( $info[ $new_reph_pos + 1 ], 'JK' ) ) { $new_reph_pos++; }
			} else {
				$new_reph_pos = $end - 1;
				while ( $new_reph_pos > 0 && self::POS_SMVD === $info[ $new_reph_pos ]['p'] ) { $new_reph_pos--; }
				if ( self::is( $info[ $new_reph_pos ], 'H' ) ) {
					for ( $i = $base + 1; $i < $new_reph_pos; $i++ ) {
						if ( 'M' === $info[ $i ]['c'] ) { $new_reph_pos--; }
					}
				}
			}
			$reph = $info[0];
			array_splice( $info, 0, 1 );
			array_splice( $info, $new_reph_pos, 0, array( $reph ) );
		}
	}

	/* ---------- GSUB ---------- */

	/** Apply the lookups of these features, in lookup order, to the items of one syllable. */
	private function gsub_stage( &$items, $tags ) {
		$masks    = array( 'rphf' => self::RPHF, 'half' => self::HALF, 'blwf' => self::BLWF, 'abvf' => self::ABVF, 'pstf' => self::PSTF, 'pref' => self::PREF, 'init' => self::INIT );
		// The Indic features see the joiners; the general ones (locl, ccmp, liga…) look through them.
		$auto     = (bool) array_intersect( $tags, array( 'locl', 'ccmp' ) );
		$features = $this->features( 'GSUB' );
		$lookups  = array();
		foreach ( $tags as $tag ) {
			foreach ( $features[ $tag ] ?? array() as $index ) {
				$mask              = $masks[ $tag ] ?? 0;
				$lookups[ $index ] = isset( $lookups[ $index ] ) && ( 0 === $lookups[ $index ] || 0 === $mask ) ? 0 : ( ( $lookups[ $index ] ?? 0 ) | $mask );
			}
		}
		ksort( $lookups );
		$general = array( 'calt', 'clig', 'liga', 'rclt' );
		foreach ( $lookups as $index => $mask ) {
			$own = $auto;
			foreach ( $general as $tag ) { $own = $own || in_array( $index, $features[ $tag ] ?? array(), true ); }
			$this->ignore = array( array( $own, $own ), array( true, $own ) );
			$lookup = $this->lookup( 'GSUB', $index );
			if ( 8 === $lookup['type'] ) { continue; }
			for ( $i = 0; $i < count( $items ); ) {
				$item = $items[ $i ];
				$next = -1;
				if ( ( ! $mask || ( $item['m'] & $mask ) ) && ! $this->skips( $item['g'], $lookup ) ) {
					foreach ( $lookup['subs'] as $sub ) {
						$next = $this->gsub_subtable( $sub[0], $sub[1], $lookup, $items, $i, $mask );
						if ( false !== $next ) { break; }
						$next = -1;
					}
				}
				$i = $next >= 0 ? $next : $i + 1;
			}
		}
	}

	/** Apply one GSUB subtable at position $i. Returns the position to go on from, or false. */
	private function gsub_subtable( $type, $at, $lookup, &$items, $i, $mask ) {
		$glyph  = $items[ $i ]['g'];
		$format = $this->u16( $at );
		if ( 1 === $type ) {
			$cover = $this->coverage( $at + $this->u16( $at + 2 ) );
			if ( ! isset( $cover[ $glyph ] ) ) { return false; }
			if ( 1 === $format ) {
				$items[ $i ]['g'] = ( $glyph + $this->s16( $at + 4 ) ) & 0xFFFF;
			} else {
				if ( $cover[ $glyph ] >= $this->u16( $at + 4 ) ) { return false; }
				$items[ $i ]['g'] = $this->u16( $at + 6 + 2 * $cover[ $glyph ] );
			}
			return $i + 1;
		}
		if ( 2 === $type ) {
			$cover = $this->coverage( $at + $this->u16( $at + 2 ) );
			if ( ! isset( $cover[ $glyph ] ) || $cover[ $glyph ] >= $this->u16( $at + 4 ) ) { return false; }
			$sequence = $at + $this->u16( $at + 6 + 2 * $cover[ $glyph ] );
			$count    = $this->u16( $sequence );
			if ( 0 === $count ) {
				array_splice( $items, $i, 1 );
				return $i;
			}
			$out = array();
			for ( $k = 0; $k < $count; $k++ ) {
				$copy      = $items[ $i ];
				$copy['g'] = $this->u16( $sequence + 2 + 2 * $k );
				if ( $count > 1 ) { $copy['mul'] = true; }
				if ( $k > 0 ) { $copy['t'] = ''; }
				$out[] = $copy;
			}
			array_splice( $items, $i, 1, $out );
			return $i + $count;
		}
		if ( 4 === $type ) {
			$cover = $this->coverage( $at + $this->u16( $at + 2 ) );
			if ( ! isset( $cover[ $glyph ] ) || $cover[ $glyph ] >= $this->u16( $at + 4 ) ) { return false; }
			$set = $at + $this->u16( $at + 6 + 2 * $cover[ $glyph ] );
			for ( $l = 0, $ligatures = $this->u16( $set ); $l < $ligatures; $l++ ) {
				$ligature   = $set + $this->u16( $set + 2 + 2 * $l );
				$components = $this->u16( $ligature + 2 );
				$found      = array( $i );
				for ( $c = 1, $j = $i; $c < $components; $c++ ) {
					$j = $this->next_index( $items, $j, $lookup );
					if ( $j < 0 || $items[ $j ]['g'] !== $this->u16( $ligature + 4 + 2 * ( $c - 1 ) ) || ( $mask && ! ( $items[ $j ]['m'] & $mask ) ) ) { continue 2; }
					$found[] = $j;
				}
				$text = '';
				foreach ( $found as $j ) { $text .= $items[ $j ]['t']; }
				$items[ $i ]['g']   = $this->u16( $ligature );
				$items[ $i ]['lig'] = true;
				$items[ $i ]['mul'] = false;
				$items[ $i ]['t']   = $text;
				foreach ( array_reverse( array_slice( $found, 1 ) ) as $j ) { array_splice( $items, $j, 1 ); }
				return $i + 1;
			}
			return false;
		}
		if ( 5 === $type || 6 === $type ) { return $this->context( 'GSUB', $type, $at, $lookup, $items, $i, $mask ); }
		return false;
	}

	/* ---------- Contextual lookups (GSUB 5 and 6, GPOS 7 and 8) ---------- */

	/**
	 * Match the input sequence that starts at $i. $test( $k, $glyph ) says whether the
	 * k-th input glyph fits. Returns the positions of the input glyphs or null.
	 */
	private function match_input( $items, $i, $count, $test, $lookup, $mask ) {
		$positions = array( $i );
		for ( $k = 1, $j = $i; $k < $count; $k++ ) {
			$j = $this->next_index( $items, $j, $lookup );
			if ( $j < 0 || ( $mask && ! ( $items[ $j ]['m'] & $mask ) ) || ! $test( $k, $items[ $j ]['g'] ) ) { return null; }
			$positions[] = $j;
		}
		return $positions;
	}

	private function match_around( $items, $from, $count, $test, $lookup, $backward ) {
		for ( $k = 0, $j = $from; $k < $count; $k++ ) {
			$j = $backward ? $this->prev_index( $items, $j, $lookup, true ) : $this->next_index( $items, $j, $lookup, true );
			if ( $j < 0 || ! $test( $k, $items[ $j ]['g'] ) ) { return false; }
		}
		return true;
	}

	/** Lookup records at $at: list of array( sequence index, lookup index ). */
	private function records( $at, $count ) {
		$records = array();
		for ( $k = 0; $k < $count; $k++ ) { $records[] = array( $this->u16( $at + 4 * $k ), $this->u16( $at + 4 * $k + 2 ) ); }
		return $records;
	}

	private function context( $table, $type, $at, $lookup, &$items, $i, $mask ) {
		$glyph  = $items[ $i ]['g'];
		$format = $this->u16( $at );
		$chain  = ( 'GSUB' === $table ? 6 : 8 ) === $type;
		$rules  = array();

		if ( 3 === $format ) {
			if ( $chain ) {
				$p         = $at + 2;
				$backtrack = array();
				for ( $k = 0, $n = $this->u16( $p ); $k < $n; $k++ ) { $backtrack[] = $at + $this->u16( $p + 2 + 2 * $k ); }
				$p    += 2 + 2 * count( $backtrack );
				$input = array();
				for ( $k = 0, $n = $this->u16( $p ); $k < $n; $k++ ) { $input[] = $at + $this->u16( $p + 2 + 2 * $k ); }
				$p        += 2 + 2 * count( $input );
				$lookahead = array();
				for ( $k = 0, $n = $this->u16( $p ); $k < $n; $k++ ) { $lookahead[] = $at + $this->u16( $p + 2 + 2 * $k ); }
				$p += 2 + 2 * count( $lookahead );
				$records = $this->records( $p + 2, $this->u16( $p ) );
			} else {
				$input = array();
				for ( $k = 0, $n = $this->u16( $at + 2 ); $k < $n; $k++ ) { $input[] = $at + $this->u16( $at + 6 + 2 * $k ); }
				$backtrack = array();
				$lookahead = array();
				$records   = $this->records( $at + 6 + 2 * count( $input ), $this->u16( $at + 4 ) );
			}
			if ( ! $input || ! isset( $this->coverage( $input[0] )[ $glyph ] ) ) { return false; }
			$by_cover = function ( $list ) { return function ( $k, $g ) use ( $list ) { return isset( $this->coverage( $list[ $k ] )[ $g ] ); }; };
			$rules[]  = array( count( $input ), $by_cover( $input ), count( $backtrack ), $by_cover( $backtrack ), count( $lookahead ), $by_cover( $lookahead ), $records );
		} else {
			$cover = $this->coverage( $at + $this->u16( $at + 2 ) );
			if ( ! isset( $cover[ $glyph ] ) ) { return false; }
			if ( 1 === $format ) {
				$index  = $cover[ $glyph ];
				$sets_n = $this->u16( $at + 4 );
				$set_at = $at + 6;
				$value  = static function ( $g ) { return $g; };
				$values = array( $value, $value, $value );
			} else {
				if ( $chain ) {
					$defs   = array( $this->classdef( $at + $this->u16( $at + 4 ) ), $this->classdef( $at + $this->u16( $at + 6 ) ), $this->classdef( $at + $this->u16( $at + 8 ) ) );
					$sets_n = $this->u16( $at + 10 );
					$set_at = $at + 12;
				} else {
					$def    = $this->classdef( $at + $this->u16( $at + 4 ) );
					$defs   = array( $def, $def, $def );
					$sets_n = $this->u16( $at + 6 );
					$set_at = $at + 8;
				}
				$values = array();
				foreach ( $defs as $def ) { $values[] = static function ( $g ) use ( $def ) { return $def[ $g ] ?? 0; }; }
				$index = $values[1]( $glyph );
			}
			if ( $index >= $sets_n || ! $this->u16( $set_at + 2 * $index ) ) { return false; }
			$set = $at + $this->u16( $set_at + 2 * $index );
			for ( $r = 0, $n = $this->u16( $set ); $r < $n; $r++ ) {
				$rule = $set + $this->u16( $set + 2 + 2 * $r );
				if ( $chain ) {
					$backtrack = array();
					for ( $k = 0, $c = $this->u16( $rule ); $k < $c; $k++ ) { $backtrack[] = $this->u16( $rule + 2 + 2 * $k ); }
					$p     = $rule + 2 + 2 * count( $backtrack );
					$input = array( null );
					for ( $k = 1, $c = $this->u16( $p ); $k < $c; $k++ ) { $input[] = $this->u16( $p + 2 * $k ); }
					$p        += 2 * max( 1, count( $input ) );
					$lookahead = array();
					for ( $k = 0, $c = $this->u16( $p ); $k < $c; $k++ ) { $lookahead[] = $this->u16( $p + 2 + 2 * $k ); }
					$p      += 2 + 2 * count( $lookahead );
					$records = $this->records( $p + 2, $this->u16( $p ) );
				} else {
					$input = array( null );
					for ( $k = 1, $c = $this->u16( $rule ); $k < $c; $k++ ) { $input[] = $this->u16( $rule + 2 + 2 * $k ); }
					$backtrack = array();
					$lookahead = array();
					$records   = $this->records( $rule + 4 + 2 * ( count( $input ) - 1 ), $this->u16( $rule + 2 ) );
				}
				$rules[] = array(
					count( $input ),
					static function ( $k, $g ) use ( $input, $values ) { return $values[1]( $g ) === $input[ $k ]; },
					count( $backtrack ),
					static function ( $k, $g ) use ( $backtrack, $values ) { return $values[0]( $g ) === $backtrack[ $k ]; },
					count( $lookahead ),
					static function ( $k, $g ) use ( $lookahead, $values ) { return $values[2]( $g ) === $lookahead[ $k ]; },
					$records,
				);
			}
		}

		foreach ( $rules as $rule ) {
			list( $input_n, $input_test, $back_n, $back_test, $ahead_n, $ahead_test, $records ) = $rule;
			$positions = $this->match_input( $items, $i, $input_n, $input_test, $lookup, $mask );
			if ( null === $positions ) { continue; }
			if ( ! $this->match_around( $items, $i, $back_n, $back_test, $lookup, true ) ) { continue; }
			if ( ! $this->match_around( $items, end( $positions ), $ahead_n, $ahead_test, $lookup, false ) ) { continue; }
			return $this->apply_records( $table, $items, $positions, $records );
		}
		return false;
	}

	/** Run the lookups of a matched rule on its input glyphs (HarfBuzz apply_lookup). Returns the position after the input. */
	private function apply_records( $table, &$items, $positions, $records ) {
		$count = count( $positions );
		$end   = end( $positions ) + 1;
		foreach ( $records as $record ) {
			list( $idx, $index ) = $record;
			if ( $idx >= $count ) { continue; }
			$before = count( $items );
			if ( ! $this->nested( $table, $index, $items, $positions[ $idx ] ) ) { continue; }
			$delta = count( $items ) - $before;
			if ( ! $delta ) { continue; }
			$end += $delta;
			if ( $end < $positions[ $idx ] ) {
				$delta += $positions[ $idx ] - $end;
				$end    = $positions[ $idx ];
			}
			$next = $idx + 1;
			if ( $delta < 0 ) {
				$delta = max( $delta, $next - $count );
				$next -= $delta;
			}
			$head      = array_slice( $positions, 0, $next );
			$head      = $delta > 0 ? array_pad( $head, $next + $delta, 0 ) : array_slice( $head, 0, $next + $delta );
			$positions = array_merge( $head, array_slice( $positions, $next ) );
			$next     += $delta;
			$count    += $delta;
			for ( $j = $idx + 1; $j < $next; $j++ ) { $positions[ $j ] = $positions[ $j - 1 ] + 1; }
			for ( ; $next < $count; $next++ ) { $positions[ $next ] += $delta; }
		}
		return $end;
	}

	/** Apply one lookup once at position $i, from inside a contextual lookup. */
	private function nested( $table, $index, &$items, $i ) {
		if ( $i >= count( $items ) ) { return false; }
		$lookup = $this->lookup( $table, $index );
		foreach ( $lookup['subs'] as $sub ) {
			$done = 'GSUB' === $table ? $this->gsub_subtable( $sub[0], $sub[1], $lookup, $items, $i, 0 ) : $this->gpos_subtable( $sub[0], $sub[1], $lookup, $items, $i );
			if ( false !== $done ) { return true; }
		}
		return false;
	}

	/* ---------- GPOS ---------- */

	private function apply_gpos( &$items ) {
		$features = $this->features( 'GPOS' );
		$lookups  = array();
		foreach ( array( 'abvm', 'blwm', 'dist', 'kern', 'mark', 'mkmk' ) as $tag ) {
			foreach ( $features[ $tag ] ?? array() as $index ) { $lookups[ $index ] = true; }
		}
		ksort( $lookups );
		$this->ignore = array( array( true, true ), array( true, true ) );
		foreach ( array_keys( $lookups ) as $index ) {
			$lookup = $this->lookup( 'GPOS', $index );
			for ( $i = 0; $i < count( $items ); ) {
				$next = -1;
				if ( ! $this->skips( $items[ $i ]['g'], $lookup ) ) {
					foreach ( $lookup['subs'] as $sub ) {
						$next = $this->gpos_subtable( $sub[0], $sub[1], $lookup, $items, $i );
						if ( false !== $next ) { break; }
						$next = -1;
					}
				}
				$i = $next >= 0 ? $next : $i + 1;
			}
		}
	}

	/** Value record: array( x placement, y placement, x advance ) and its size in bytes. */
	private function value( $at, $format ) {
		$values = array( 0, 0, 0, 0 );
		$p      = $at;
		foreach ( array( 1, 2, 4, 8 ) as $k => $bit ) {
			if ( $format & $bit ) { $values[ $k ] = $this->s16( $p ); $p += 2; }
		}
		return $values;
	}

	private static function value_size( $format ) {
		return 2 * substr_count( decbin( $format & 0xFF ), '1' );
	}

	private static function add_value( &$item, $value ) {
		$item['xo'] += $value[0];
		$item['yo'] += $value[1];
		$item['xa'] += $value[2];
	}

	private function anchor( $at ) {
		return array( $this->s16( $at + 2 ), $this->s16( $at + 4 ) );
	}

	/** Apply one GPOS subtable at position $i. Returns the position to go on from, or false. */
	private function gpos_subtable( $type, $at, $lookup, &$items, $i ) {
		$glyph  = $items[ $i ]['g'];
		$format = $this->u16( $at );
		if ( 1 === $type ) {
			$cover = $this->coverage( $at + $this->u16( $at + 2 ) );
			if ( ! isset( $cover[ $glyph ] ) ) { return false; }
			$vf = $this->u16( $at + 4 );
			self::add_value( $items[ $i ], $this->value( 1 === $format ? $at + 6 : $at + 8 + $cover[ $glyph ] * self::value_size( $vf ), $vf ) );
			return $i + 1;
		}
		if ( 2 === $type ) {
			$cover = $this->coverage( $at + $this->u16( $at + 2 ) );
			if ( ! isset( $cover[ $glyph ] ) ) { return false; }
			$j = $this->next_index( $items, $i, $lookup );
			if ( $j < 0 ) { return false; }
			$second = $items[ $j ]['g'];
			$vf1    = $this->u16( $at + 4 );
			$vf2    = $this->u16( $at + 6 );
			$size1  = self::value_size( $vf1 );
			$size2  = self::value_size( $vf2 );
			$record = 0;
			if ( 1 === $format ) {
				$set = $at + $this->u16( $at + 10 + 2 * $cover[ $glyph ] );
				for ( $k = 0, $n = $this->u16( $set ); $k < $n; $k++ ) {
					$at_k = $set + 2 + $k * ( 2 + $size1 + $size2 );
					if ( $this->u16( $at_k ) === $second ) { $record = $at_k + 2; break; }
				}
			} else {
				$c1 = $this->classdef( $at + $this->u16( $at + 8 ) )[ $glyph ] ?? 0;
				$c2 = $this->classdef( $at + $this->u16( $at + 10 ) )[ $second ] ?? 0;
				if ( $c1 < $this->u16( $at + 12 ) && $c2 < $this->u16( $at + 14 ) ) { $record = $at + 16 + ( $c1 * $this->u16( $at + 14 ) + $c2 ) * ( $size1 + $size2 ); }
			}
			if ( ! $record ) { return false; }
			self::add_value( $items[ $i ], $this->value( $record, $vf1 ) );
			self::add_value( $items[ $j ], $this->value( $record + $size1, $vf2 ) );
			return $vf2 ? $j + 1 : $j;
		}
		if ( 4 === $type || 5 === $type || 6 === $type ) {
			$marks = $this->coverage( $at + $this->u16( $at + 2 ) );
			if ( ! isset( $marks[ $glyph ] ) ) { return false; }
			if ( 6 === $type ) {
				$j = $this->prev_index( $items, $i, $lookup );
				if ( $j < 0 || 3 !== ( $this->glyph_class[ $items[ $j ]['g'] ] ?? 0 ) ) { return false; }
			} else {
				for ( $j = $i - 1; $j >= 0 && ( 3 === ( $this->glyph_class[ $items[ $j ]['g'] ] ?? 0 ) || $this->ignorable( $items[ $j ], false ) ); $j-- ) {} // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
				if ( $j < 0 ) { return false; }
			}
			$bases = $this->coverage( $at + $this->u16( $at + 4 ) );
			if ( ! isset( $bases[ $items[ $j ]['g'] ] ) ) { return false; }
			$classes    = $this->u16( $at + 6 );
			$mark_array = $at + $this->u16( $at + 8 );
			$base_array = $at + $this->u16( $at + 10 );
			$mark_rec   = $mark_array + 2 + 4 * $marks[ $glyph ];
			$class      = $this->u16( $mark_rec );
			if ( $class >= $classes ) { return false; }
			if ( 5 === $type ) {
				// Mark on a ligature: use the anchors of its last component.
				$attach     = $base_array + $this->u16( $base_array + 2 + 2 * $bases[ $items[ $j ]['g'] ] );
				$components = $this->u16( $attach );
				if ( ! $components ) { return false; }
				$offset = $this->u16( $attach + 2 + 2 * ( ( $components - 1 ) * $classes + $class ) );
				$base   = $offset ? $attach + $offset : 0;
			} else {
				$offset = $this->u16( $base_array + 2 + 2 * ( $bases[ $items[ $j ]['g'] ] * $classes + $class ) );
				$base   = $offset ? $base_array + $offset : 0;
			}
			if ( ! $base ) { return false; }
			list( $bx, $by ) = $this->anchor( $base );
			list( $mx, $my ) = $this->anchor( $mark_array + $this->u16( $mark_rec + 2 ) );
			$items[ $i ]['xo']  = $bx - $mx;
			$items[ $i ]['yo']  = $by - $my;
			$items[ $i ]['att'] = $j;
			return $i + 1;
		}
		if ( 7 === $type || 8 === $type ) { return $this->context( 'GPOS', $type, $at, $lookup, $items, $i, 0 ); }
		return false;
	}

	/* ---------- The font inside the PDF ---------- */

	/**
	 * A copy of the font for the PDF that keeps every glyph number but only the
	 * outlines of the glyphs that were used (and the parts they are made of).
	 */
	public function subset( array $glyphs ) {
		$glyf = $this->tables['glyf'][0];
		$loca = $this->tables['loca'][0];
		$keep = array( 0 => true );
		$todo = array_map( 'intval', $glyphs );
		while ( $todo ) {
			$g = array_pop( $todo );
			if ( isset( $keep[ $g ] ) && 0 !== $g ) { continue; }
			$keep[ $g ] = true;
			list( $start, $end ) = $this->glyph_range( $g );
			if ( $end - $start < 10 || $this->s16( $glyf + $start ) >= 0 ) { continue; }
			// A composite glyph: keep the glyphs it is built from.
			$p = $glyf + $start + 10;
			do {
				$flags  = $this->u16( $p );
				$todo[] = $this->u16( $p + 2 );
				$p     += 4 + ( $flags & 1 ? 4 : 2 ) + ( $flags & 8 ? 2 : ( $flags & 0x40 ? 4 : ( $flags & 0x80 ? 8 : 0 ) ) );
			} while ( $flags & 0x20 );
		}

		$new_glyf = '';
		$new_loca = '';
		for ( $g = 0; $g < $this->num_glyphs; $g++ ) {
			$new_loca .= pack( 'N', strlen( $new_glyf ) );
			if ( isset( $keep[ $g ] ) ) {
				list( $start, $end ) = $this->glyph_range( $g );
				$new_glyf .= substr( $this->data, $glyf + $start, $end - $start );
				$new_glyf .= str_repeat( "\0", ( 4 - strlen( $new_glyf ) % 4 ) % 4 );
			}
		}
		$new_loca .= pack( 'N', strlen( $new_glyf ) );

		$tables = array();
		foreach ( array( 'OS/2', 'cmap', 'cvt ', 'fpgm', 'hhea', 'hmtx', 'maxp', 'name', 'post', 'prep' ) as $tag ) {
			if ( isset( $this->tables[ $tag ] ) ) { $tables[ $tag ] = substr( $this->data, $this->tables[ $tag ][0], $this->tables[ $tag ][1] ); }
		}
		$head = substr( $this->data, $this->tables['head'][0], $this->tables['head'][1] );
		$head = substr_replace( $head, "\0\0\0\0", 8, 4 );
		$head = substr_replace( $head, "\0\1", 50, 2 );
		$tables['head'] = $head;
		$tables['glyf'] = $new_glyf;
		$tables['loca'] = $new_loca;
		ksort( $tables, SORT_STRING );

		$count  = count( $tables );
		$power  = 1 << (int) floor( log( $count, 2 ) );
		$dir    = pack( 'Nnnnn', 0x00010000, $count, $power * 16, (int) log( $power, 2 ), $count * 16 - $power * 16 );
		$body   = '';
		$offset = 12 + 16 * $count;
		foreach ( $tables as $tag => $table ) {
			$dir    .= $tag . pack( 'NNN', self::checksum( $table ), $offset + strlen( $body ), strlen( $table ) );
			$body   .= $table . str_repeat( "\0", ( 4 - strlen( $table ) % 4 ) % 4 );
		}
		$font   = $dir . $body;
		$adjust = ( 0xB1B0AFBA - self::checksum( $font ) ) & 0xFFFFFFFF;
		$head_at = $offset;
		foreach ( $tables as $tag => $table ) {
			if ( 'head' === $tag ) { break; }
			$head_at += strlen( $table ) + ( 4 - strlen( $table ) % 4 ) % 4;
		}
		return substr_replace( $font, pack( 'N', $adjust ), $head_at + 8, 4 );
	}

	private function glyph_range( $glyph ) {
		$loca = $this->tables['loca'][0];
		if ( $glyph >= $this->num_glyphs ) { return array( 0, 0 ); }
		if ( $this->loca_long ) { return array( $this->u32( $loca + 4 * $glyph ), $this->u32( $loca + 4 * $glyph + 4 ) ); }
		return array( 2 * $this->u16( $loca + 2 * $glyph ), 2 * $this->u16( $loca + 2 * $glyph + 2 ) );
	}

	private static function checksum( $data ) {
		$data .= str_repeat( "\0", ( 4 - strlen( $data ) % 4 ) % 4 );
		$sum   = 0;
		foreach ( unpack( 'N*', $data ) as $word ) { $sum = ( $sum + $word ) & 0xFFFFFFFF; }
		return $sum;
	}
}
