<?php
/**
 * The report card download.
 *
 * The portal writes the PDF itself with the small writer below, so no library
 * has to be installed on the server. The writer knows A4 pages, text (also
 * wrapped into paragraphs), lines, boxes and JPEG photos. It is used for the
 * report card here and for certificates, fee receipts and ID cards in their
 * own files.
 *
 * English text is written in Helvetica. Hindi (Devanagari) text and the rupee
 * sign are written with the Noto Sans Devanagari font from assets/fonts/,
 * shaped and embedded by MAGS_ERP_Font (includes/pdf-font.php). Only a text in
 * a script neither font has (Tamil or Urdu, for example) makes a document open
 * as a print page instead, which the browser can save as a PDF.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAGS_ERP_PDF {
	const WIDTH  = 595.28;
	const HEIGHT = 841.89;

	/** Widths of the characters 32 to 126 in 1/1000 of the font size: Helvetica, then Helvetica-Bold. */
	private static $widths = array(
		array( 278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556, 1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556, 333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556, 556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584 ),
		array( 278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611, 975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556, 333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611, 611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584 ),
	);

	private $pages  = array();
	private $images = array();
	/** Glyphs used from the embedded fonts: 0 regular, 1 bold => glyph => text, for the font copy and copy-and-paste. */
	private $used = array( array(), array() );

	public function add_page() {
		$this->pages[] = '';
	}

	/** The embedded font for Hindi and the rupee sign (regular or bold), or null when its file is missing. */
	public static function unicode_font( $bold ) {
		$file = MAGS_ERP_PATH . 'assets/fonts/NotoSansDevanagari-' . ( $bold ? 'Bold' : 'Regular' ) . '.ttf';
		return MAGS_ERP_Font::load( (string) apply_filters( 'mags_erp_pdf_font', $file, (bool) $bold ) );
	}

	/** True for a character that Helvetica has (Windows-1252). */
	private static function is_latin( $char ) {
		static $known = array();
		if ( ! isset( $known[ $char ] ) ) {
			$known[ $char ] = 1 === strlen( $char ) ? ord( $char ) >= 32 : ( '?' !== mb_convert_encoding( $char, 'Windows-1252', 'UTF-8' ) );
		}
		return $known[ $char ];
	}

	/**
	 * A text cut into runs for the two fonts: list of array( embedded font?, text ).
	 * Spaces stay in Helvetica, so "राहुल शर्मा" is three runs.
	 */
	private static function runs( $text ) {
		$runs = array();
		foreach ( preg_split( '//u', preg_replace( '/\s+/u', ' ', (string) $text ), -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
			$unicode = ! self::is_latin( $char );
			$last    = count( $runs ) - 1;
			if ( $last >= 0 && $runs[ $last ][0] === $unicode ) {
				$runs[ $last ][1] .= $char;
			} else {
				$runs[] = array( $unicode, $char );
			}
		}
		return $runs;
	}

	/** True when every character of the text can be written: by Helvetica or by the embedded font. */
	public static function can_write( $text ) {
		$text = (string) $text;
		if ( ! preg_match( '/[^\x20-\x7E]/', $text ) ) { return true; }
		foreach ( self::runs( $text ) as $run ) {
			if ( ! $run[0] ) { continue; }
			foreach ( array( false, true ) as $bold ) {
				$font = self::unicode_font( $bold );
				if ( ! $font ) { return false; }
				foreach ( preg_split( '//u', $run[1], -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
					if ( ! $font->has_char( mb_ord( $char, 'UTF-8' ) ) ) { return false; }
				}
			}
		}
		return true;
	}

	private static function encode( $text ) {
		return mb_convert_encoding( preg_replace( '/\s+/u', ' ', (string) $text ), 'Windows-1252', 'UTF-8' );
	}

	/** Width of one run in points. */
	private function run_width( $run, $size, $bold ) {
		if ( $run[0] ) {
			$font = self::unicode_font( $bold );
			return $font ? $font->width( $run[1] ) * $size / $font->units_per_em : 0;
		}
		$units = 0;
		foreach ( unpack( 'C*', self::encode( $run[1] ) ) as $byte ) {
			$units += $byte >= 32 && $byte <= 126 ? self::$widths[ $bold ? 1 : 0 ][ $byte - 32 ] : ( 133 === $byte ? 1000 : ( $bold ? 611 : 556 ) );
		}
		return $units * $size / 1000;
	}

	public function text_width( $text, $size, $bold = false ) {
		$width = 0;
		foreach ( self::runs( $text ) as $run ) { $width += $this->run_width( $run, $size, $bold ); }
		return $width;
	}

	/** Shorten a text with "…" until it fits the width. Hindi is cut between letters, never inside one. */
	public function fit( $text, $size, $bold, $max_width ) {
		$text = (string) $text;
		if ( $this->text_width( $text, $size, $bold ) <= $max_width ) { return $text; }
		$parts = preg_split( '/(\X)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) ?: array( $text );
		$cut   = static function ( $count ) use ( $parts ) { return preg_replace( '/[\x{094D}\x{200C}\x{200D}]+$/u', '', rtrim( implode( '', array_slice( $parts, 0, $count ) ) ) ); };
		$low   = 1;
		$high  = count( $parts );
		while ( $low < $high ) {
			$mid = intdiv( $low + $high + 1, 2 );
			if ( $this->text_width( $cut( $mid ) . '…', $size, $bold ) <= $max_width ) { $low = $mid; } else { $high = $mid - 1; }
		}
		return $cut( $low ) . '…';
	}

	private static function number( $value ) {
		return rtrim( rtrim( sprintf( '%.3F', $value ), '0' ), '.' );
	}

	private static function colour( $rgb, $operator ) {
		return self::number( $rgb[0] ) . ' ' . self::number( $rgb[1] ) . ' ' . self::number( $rgb[2] ) . ' ' . $operator;
	}

	private function add( $operators ) {
		if ( ! $this->pages ) { $this->add_page(); }
		$this->pages[ count( $this->pages ) - 1 ] .= $operators . "\n";
	}

	/** Write one line of text. $y is the baseline measured from the top of the page; $align is L, C or R around $x. */
	public function text( $x, $y, $text, $size = 10, $bold = false, $align = 'L', $rgb = array( 0.157, 0.157, 0.314 ) ) {
		$runs  = self::runs( $text );
		$width = 0;
		if ( 'L' !== $align ) {
			foreach ( $runs as $run ) { $width += $this->run_width( $run, $size, $bold ); }
		}
		$x -= 'R' === $align ? $width : ( 'C' === $align ? $width / 2 : 0 );
		foreach ( $runs as $run ) {
			if ( $run[0] ) {
				$this->glyph_run( $x, self::HEIGHT - $y, $run[1], $size, $bold, $rgb );
			} else {
				$bytes = strtr( self::encode( $run[1] ), array( '\\' => '\\\\', '(' => '\\(', ')' => '\\)' ) );
				$this->add( 'BT /F' . ( $bold ? 2 : 1 ) . ' ' . self::number( $size ) . ' Tf ' . self::colour( $rgb, 'rg' ) . ' ' . self::number( $x ) . ' ' . self::number( self::HEIGHT - $y ) . ' Td (' . $bytes . ') Tj ET' );
			}
			$x += $this->run_width( $run, $size, $bold );
		}
	}

	/**
	 * Write a run with the embedded font, glyph by glyph where the shaping moved
	 * a glyph (vowel signs, reph). $baseline is in PDF coordinates. The run is
	 * marked with its real text, so searching and copying the PDF find the name.
	 */
	private function glyph_run( $x, $baseline, $text, $size, $bold, $rgb ) {
		$font = self::unicode_font( $bold );
		if ( ! $font ) { return; }
		$scale  = $size / $font->units_per_em;
		$ops    = '/Span << /ActualText <FEFF' . strtoupper( bin2hex( mb_convert_encoding( $text, 'UTF-16BE', 'UTF-8' ) ) ) . '> >> BDC BT /F' . ( $bold ? 4 : 3 ) . ' ' . self::number( $size ) . ' Tf ' . self::colour( $rgb, 'rg' );
		$hex    = '';
		$expect = null;
		$pen    = $x;
		foreach ( $font->shape( $text ) as $glyph ) {
			if ( ! $glyph['hide'] ) {
				$gx = $pen + $glyph['xo'] * $scale;
				$gy = $baseline + $glyph['yo'] * $scale;
				if ( ! isset( $this->used[ $bold ? 1 : 0 ][ $glyph['g'] ] ) || '' === $this->used[ $bold ? 1 : 0 ][ $glyph['g'] ] ) { $this->used[ $bold ? 1 : 0 ][ $glyph['g'] ] = $glyph['t']; }
				if ( null === $expect || abs( $gx - $expect[0] ) > 0.001 || abs( $gy - $expect[1] ) > 0.001 ) {
					if ( '' !== $hex ) { $ops .= ' <' . $hex . '> Tj'; }
					$ops .= ' 1 0 0 1 ' . self::number( $gx ) . ' ' . self::number( $gy ) . ' Tm';
					$hex  = '';
				}
				$hex   .= sprintf( '%04X', $glyph['g'] );
				$expect = array( $gx + $font->advance( $glyph['g'] ) * $scale, $gy );
			}
			$pen += $glyph['xa'] * $scale;
		}
		$this->add( $ops . ( '' !== $hex ? ' <' . $hex . '> Tj' : '' ) . ' ET EMC' );
	}

	public function line( $x1, $y1, $x2, $y2, $width = 0.6, $rgb = array( 0.78, 0.8, 0.88 ) ) {
		$this->add( self::number( $width ) . ' w ' . self::colour( $rgb, 'RG' ) . ' ' . self::number( $x1 ) . ' ' . self::number( self::HEIGHT - $y1 ) . ' m ' . self::number( $x2 ) . ' ' . self::number( self::HEIGHT - $y2 ) . ' l S' );
	}

	/** A filled box; $y is its top edge measured from the top of the page. */
	public function box( $x, $y, $width, $height, $rgb ) {
		$this->add( self::colour( $rgb, 'rg' ) . ' ' . self::number( $x ) . ' ' . self::number( self::HEIGHT - $y - $height ) . ' ' . self::number( $width ) . ' ' . self::number( $height ) . ' re f' );
	}

	/** The outline of a box; $dash draws it as a cutting line. */
	public function rect( $x, $y, $width, $height, $line = 0.6, $rgb = array( 0.78, 0.8, 0.88 ), $dash = false ) {
		$this->add( self::number( $line ) . ' w ' . ( $dash ? '[3 3] 0 d ' : '' ) . self::colour( $rgb, 'RG' ) . ' ' . self::number( $x ) . ' ' . self::number( self::HEIGHT - $y - $height ) . ' ' . self::number( $width ) . ' ' . self::number( $height ) . ' re S' . ( $dash ? ' [] 0 d' : '' ) );
	}

	/** Break a text into lines that fit the width. A single word that is too long is shortened. */
	public function wrap( $text, $size, $bold, $max_width ) {
		$lines = array();
		$line  = '';
		foreach ( preg_split( '/\s+/u', trim( (string) $text ), -1, PREG_SPLIT_NO_EMPTY ) as $word ) {
			$try = '' === $line ? $word : $line . ' ' . $word;
			if ( $this->text_width( $try, $size, $bold ) <= $max_width ) { $line = $try; continue; }
			if ( '' !== $line ) { $lines[] = $line; }
			$line = $this->fit( $word, $size, $bold, $max_width );
		}
		if ( '' !== $line ) { $lines[] = $line; }
		return $lines;
	}

	/**
	 * Write a paragraph that may mix normal and bold words. $parts is a list of
	 * array( text, bold ). Returns the baseline of the line after the paragraph.
	 */
	public function paragraph( $x, $y, $parts, $size, $max_width, $line_height, $rgb = array( 0.157, 0.157, 0.314 ) ) {
		$space = $this->text_width( ' ', $size, false );
		$at    = $x;
		foreach ( $parts as $part ) {
			$bold = ! empty( $part[1] );
			foreach ( preg_split( '/\s+/u', trim( (string) $part[0] ), -1, PREG_SPLIT_NO_EMPTY ) as $word ) {
				$word  = $this->fit( $word, $size, $bold, $max_width );
				$width = $this->text_width( $word, $size, $bold );
				// Commas and full stops stay with the word before them.
				$glue = $at > $x && preg_match( '/^[,.;:)]/', $word );
				if ( $at > $x && ! $glue && $at + $space + $width > $x + $max_width ) {
					$at = $x;
					$y += $line_height;
				}
				if ( $at > $x && ! $glue ) { $at += $space; }
				$this->text( $at, $y, $word, $size, $bold, 'L', $rgb );
				$at += $width;
			}
		}
		return $y + $line_height;
	}

	/**
	 * Place a JPEG photo. Returns false (and draws nothing) when the file is not
	 * a JPEG the PDF can show, so the caller can draw a placeholder instead.
	 */
	public function jpeg( $path, $x, $y, $width, $height ) {
		$info = is_string( $path ) && is_file( $path ) ? @getimagesize( $path ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$data = $info && IMAGETYPE_JPEG === $info[2] ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions
		$spaces = array( 1 => '/DeviceGray', 3 => '/DeviceRGB', 4 => '/DeviceCMYK /Decode [1 0 1 0 1 0 1 0]' );
		$channels = (int) ( $info['channels'] ?? 3 );
		if ( ! $data || ! isset( $spaces[ $channels ] ) || $info[0] < 1 || $info[1] < 1 ) { return false; }
		$key = md5( $data );
		if ( ! isset( $this->images[ $key ] ) ) { $this->images[ $key ] = array( 'name' => 'Im' . ( count( $this->images ) + 1 ), 'data' => $data, 'width' => (int) $info[0], 'height' => (int) $info[1], 'space' => $spaces[ $channels ] ); }
		$this->add( 'q ' . self::number( $width ) . ' 0 0 ' . self::number( $height ) . ' ' . self::number( $x ) . ' ' . self::number( self::HEIGHT - $y - $height ) . ' cm /' . $this->images[ $key ]['name'] . ' Do Q' );
		return true;
	}

	/** PDF objects of one embedded font, numbered from $first: the font dictionary is $first. */
	private function font_objects( $bold, $first ) {
		$font  = self::unicode_font( $bold );
		$used  = $this->used[ $bold ? 1 : 0 ];
		ksort( $used );
		$em    = 1000 / $font->units_per_em;
		$tag   = strtoupper( substr( preg_replace( '/[^a-f]/', '', md5( implode( ',', array_keys( $used ) ) . $font->name ) . 'abcdef' ), 0, 6 ) );
		$name  = '/' . $tag . '+' . $font->name;
		$file  = $font->subset( array_keys( $used ) );
		$pack  = function_exists( 'gzcompress' ) ? gzcompress( $file ) : false;
		$widths = '';
		$cmap   = array();
		foreach ( $used as $glyph => $text ) {
			$widths .= ' ' . $glyph . ' [' . round( $font->advance( $glyph ) * $em ) . ']';
			if ( '' !== $text ) { $cmap[] = sprintf( '<%04X> <%s>', $glyph, strtoupper( bin2hex( mb_convert_encoding( $text, 'UTF-16BE', 'UTF-8' ) ) ) ); }
		}
		$unicode = "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n/CMapName /Adobe-Identity-UCS def\n/CMapType 2 def\n1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n";
		foreach ( array_chunk( $cmap, 100 ) as $chunk ) { $unicode .= count( $chunk ) . " beginbfchar\n" . implode( "\n", $chunk ) . "\nendbfchar\n"; }
		$unicode .= "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";
		$box = array_map( static function ( $value ) use ( $em ) { return round( $value * $em ); }, $font->bbox );

		return array(
			$first     => '<< /Type /Font /Subtype /Type0 /BaseFont ' . $name . ' /Encoding /Identity-H /DescendantFonts [' . ( $first + 1 ) . ' 0 R] /ToUnicode ' . ( $first + 4 ) . ' 0 R >>',
			$first + 1 => '<< /Type /Font /Subtype /CIDFontType2 /BaseFont ' . $name . ' /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor ' . ( $first + 2 ) . ' 0 R /DW 0 /W [' . $widths . ' ] /CIDToGIDMap /Identity >>',
			$first + 2 => '<< /Type /FontDescriptor /FontName ' . $name . ' /Flags 4 /FontBBox [' . implode( ' ', $box ) . '] /ItalicAngle 0 /Ascent ' . round( $font->ascent * $em ) . ' /Descent ' . round( $font->descent * $em ) . ' /CapHeight ' . round( $font->cap_height * $em ) . ' /StemV ' . ( $bold ? 120 : 80 ) . ' /FontFile2 ' . ( $first + 3 ) . ' 0 R >>',
			$first + 3 => '<< /Length ' . strlen( false !== $pack ? $pack : $file ) . ' /Length1 ' . strlen( $file ) . ( false !== $pack ? ' /Filter /FlateDecode' : '' ) . " >>\nstream\n" . ( false !== $pack ? $pack : $file ) . "\nendstream",
			$first + 4 => '<< /Length ' . strlen( $unicode ) . " >>\nstream\n" . $unicode . "\nendstream",
		);
	}

	/** The finished PDF file as a string of bytes. */
	public function output( $title ) {
		$objects = array(
			1 => '<< /Type /Catalog /Pages 2 0 R >>',
			3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
			4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
			5 => '<< /Title <FEFF' . strtoupper( bin2hex( mb_convert_encoding( (string) $title, 'UTF-16BE', 'UTF-8' ) ) ) . '> /Producer (MAGS ERP Portal) >>',
		);
		$kids     = array();
		$xobjects = '';
		$image_no = 6 + 2 * count( $this->pages );
		foreach ( $this->images as $image ) {
			$xobjects            .= ' /' . $image['name'] . ' ' . $image_no . ' 0 R';
			$objects[ $image_no ] = '<< /Type /XObject /Subtype /Image /Width ' . $image['width'] . ' /Height ' . $image['height'] . ' /ColorSpace ' . $image['space'] . ' /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen( $image['data'] ) . " >>\nstream\n" . $image['data'] . "\nendstream";
			$image_no++;
		}
		$fonts = ' /F1 3 0 R /F2 4 0 R';
		foreach ( array( 0, 1 ) as $bold ) {
			if ( ! $this->used[ $bold ] ) { continue; }
			$fonts   .= ' /F' . ( 3 + $bold ) . ' ' . $image_no . ' 0 R';
			$objects += $this->font_objects( (bool) $bold, $image_no );
			$image_no += 5;
		}
		foreach ( $this->pages as $index => $content ) {
			$page_no               = 6 + 2 * $index;
			$kids[]                = $page_no . ' 0 R';
			$objects[ $page_no ]     = '<< /Type /Page /Parent 2 0 R /Resources << /Font <<' . $fonts . ' >>' . ( $xobjects ? ' /XObject <<' . $xobjects . ' >>' : '' ) . ' >> /Contents ' . ( $page_no + 1 ) . ' 0 R >>';
			$objects[ $page_no + 1 ] = '<< /Length ' . strlen( $content ) . " >>\nstream\n" . $content . 'endstream';
		}
		$objects[2] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . count( $kids ) . ' /MediaBox [0 0 ' . self::WIDTH . ' ' . self::HEIGHT . '] >>';
		ksort( $objects );

		$pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array();
		foreach ( $objects as $number => $body ) {
			$offsets[ $number ] = strlen( $pdf );
			$pdf               .= $number . " 0 obj\n" . $body . "\nendobj\n";
		}
		$xref = strlen( $pdf );
		$pdf .= 'xref' . "\n" . '0 ' . ( count( $objects ) + 1 ) . "\n" . "0000000000 65535 f \n";
		foreach ( $offsets as $offset ) { $pdf .= sprintf( "%010d 00000 n \n", $offset ); }
		return $pdf . 'trailer' . "\n" . '<< /Size ' . ( count( $objects ) + 1 ) . ' /Root 1 0 R /Info 5 0 R >>' . "\nstartxref\n" . $xref . "\n%%EOF\n";
	}
}

/**
 * Everything the report card shows for one student in one exam, or null when
 * the student has no paper in it.
 */
function mags_erp_report_card_data( $exam, $student ) {
	$result = mags_erp_student_result( $exam, $student );
	if ( ! $result['rows'] ) { return null; }

	$session = mags_erp_session_range( $exam->start_date ?: ( $exam->published_at ? substr( $exam->published_at, 0, 10 ) : '' ) );
	$counts  = mags_erp_student_summary( (int) $student->id, $session['start'], min( $session['end'], mags_erp_today() ) );
	return array(
		'school'     => mags_erp_school_name(),
		'exam'       => $exam->name,
		'session'    => $session['label'],
		'student'    => $student,
		'class'      => mags_erp_all_class_labels()[ $result['class_id'] ] ?? '',
		'result'     => $result,
		'attendance' => array( 'present' => $counts['P'], 'marked' => array_sum( $counts ), 'percent' => mags_erp_percentage( $counts ) ),
		'made_on'    => mags_erp_format_date( mags_erp_today() ),
	);
}

/** Can the PDF fonts write every name on this report card? */
function mags_erp_report_card_fits_pdf( $card ) {
	$texts = array( $card['school'], $card['exam'], $card['class'], $card['student']->full_name, $card['student']->admission_no, $card['student']->roll_no );
	foreach ( $card['result']['rows'] as $row ) { $texts[] = $row->subject_name; }
	foreach ( $texts as $text ) { if ( ! MAGS_ERP_PDF::can_write( $text ) ) { return false; } }
	return true;
}

/** Draw the report card. Returns the PDF file as a string. */
function mags_erp_report_card_pdf( $card ) {
	$navy   = array( 0.157, 0.157, 0.314 );
	$orange = array( 0.973, 0.471, 0 );
	$muted  = array( 0.345, 0.353, 0.471 );
	$soft   = array( 0.933, 0.941, 0.984 );
	$white  = array( 1, 1, 1 );
	$left   = 50;
	$right  = MAGS_ERP_PDF::WIDTH - 50;
	$mid    = MAGS_ERP_PDF::WIDTH / 2;
	$col_max   = 410;
	$col_marks = $right - 12;

	$pdf = new MAGS_ERP_PDF();
	$pdf->add_page();
	$pdf->box( 0, 0, MAGS_ERP_PDF::WIDTH, 96, $navy );
	$pdf->box( 0, 96, MAGS_ERP_PDF::WIDTH, 4, $orange );
	$pdf->text( $mid, 46, $pdf->fit( $card['school'], 18, true, $right - $left ), 18, true, 'C', $white );
	$pdf->text( $mid, 72, 'REPORT CARD', 11, true, 'C', $orange );
	$pdf->text( $mid, 134, $pdf->fit( $card['exam'], 15, true, $right - $left ), 15, true, 'C', $navy );
	$pdf->text( $mid, 152, 'Session ' . $card['session'], 10, false, 'C', $muted );

	$pdf->box( $left, 168, $right - $left, 78, $soft );
	$facts = array(
		array( $left + 14, 186, 'STUDENT', $card['student']->full_name, 240 ),
		array( 330, 186, 'CLASS', $card['class'], 200 ),
		array( $left + 14, 220, 'ADMISSION NO.', $card['student']->admission_no, 240 ),
		array( 330, 220, 'ROLL NO.', '' !== $card['student']->roll_no ? $card['student']->roll_no : '-', 200 ),
	);
	foreach ( $facts as $fact ) {
		$pdf->text( $fact[0], $fact[1], $fact[2], 7.5, true, 'L', $muted );
		$pdf->text( $fact[0], $fact[1] + 14, $pdf->fit( $fact[3], 11.5, true, $fact[4] ), 11.5, true, 'L', $navy );
	}

	$head = static function ( $top ) use ( $pdf, $left, $right, $navy, $white, $col_max, $col_marks ) {
		$pdf->box( $left, $top, $right - $left, 24, $navy );
		$pdf->text( $left + 12, $top + 16, 'Subject', 10, true, 'L', $white );
		$pdf->text( $col_max, $top + 16, 'Maximum marks', 10, true, 'R', $white );
		$pdf->text( $col_marks, $top + 16, 'Marks obtained', 10, true, 'R', $white );
		return $top + 24;
	};
	$top     = $head( 264 );
	$legend  = false;
	foreach ( $card['result']['rows'] as $index => $row ) {
		if ( $top + 24 > 770 ) {
			$pdf->add_page();
			$top = $head( 60 );
		}
		if ( 1 === $index % 2 ) { $pdf->box( $left, $top, $right - $left, 24, array( 0.97, 0.975, 0.995 ) ); }
		$pdf->text( $left + 12, $top + 16, $pdf->fit( $row->subject_name, 11, false, 250 ), 11, false, 'L', $navy );
		$pdf->text( $col_max, $top + 16, mags_erp_format_marks( $row->max_marks ), 11, false, 'R', $navy );
		$pdf->text( $col_marks, $top + 16, str_replace( '–', '-', mags_erp_marks_text( $row ) ), 11, true, 'R', $navy );
		$pdf->line( $left, $top + 24, $right, $top + 24 );
		$legend = $legend || ! $row->has_result || ! empty( $row->absent );
		$top   += 24;
	}
	if ( $top + 200 > 800 ) {
		$pdf->add_page();
		$top = 60;
	}

	$pdf->box( $left, $top, $right - $left, 28, $soft );
	$pdf->text( $left + 12, $top + 18, 'Total', 11.5, true, 'L', $navy );
	$pdf->text( $col_max, $top + 18, mags_erp_format_marks( $card['result']['max'] ), 11.5, true, 'R', $navy );
	$pdf->text( $col_marks, $top + 18, mags_erp_format_marks( $card['result']['total'] ), 11.5, true, 'R', $navy );
	$top += 28;
	$pdf->box( $left, $top, $right - $left, 28, $navy );
	$pdf->text( $left + 12, $top + 18, 'Percentage', 11.5, true, 'L', $white );
	$pdf->text( $col_marks, $top + 18, null === $card['result']['percent'] ? '-' : $card['result']['percent'] . '%', 12.5, true, 'R', $white );
	$top += 28;

	if ( $legend ) {
		$top += 16;
		$pdf->text( $left, $top, 'AB = absent, counted as 0.   - = marks not entered, not counted in the total.', 8.5, false, 'L', $muted );
	}
	if ( $card['attendance']['marked'] ) {
		$top += 24;
		$pdf->text( $left, $top, sprintf( 'Attendance in session %s: present on %d of %d days marked (%s%%).', $card['session'], $card['attendance']['present'], $card['attendance']['marked'], $card['attendance']['percent'] ), 10, false, 'L', $navy );
	}

	$sign = max( $top + 90, 700 );
	foreach ( array( 'Class teacher' => $left, 'Principal' => $mid - 70, 'Parent / Guardian' => $right - 140 ) as $who => $x ) {
		$pdf->line( $x, $sign, $x + 140, $sign, 0.8, $muted );
		$pdf->text( $x + 70, $sign + 14, $who, 9, false, 'C', $muted );
	}
	$pdf->text( $mid, 806, 'Made from the school portal on ' . $card['made_on'] . '.', 8, false, 'C', $muted );
	return $pdf->output( 'Report card - ' . $card['student']->full_name . ' - ' . $card['exam'] );
}

/**
 * /portal/report-card/?exam=… : a student gets their own report card for a
 * published exam; the office can get it for any student (&student=…).
 */
function mags_erp_serve_report_card() {
	$role    = mags_erp_user_role();
	$exam    = mags_erp_get_exam( mags_erp_get_int( 'exam' ) );
	$student = null;
	if ( 'student' === $role ) {
		$student = mags_erp_get_student_by_user( get_current_user_id() );
		$exam    = $exam && $exam->published_at ? $exam : null;
	} elseif ( 'office' === $role ) {
		$student = mags_erp_get_student( mags_erp_get_int( 'student' ) );
	}
	$card = $exam && $student ? mags_erp_report_card_data( $exam, $student ) : null;
	if ( ! $card ) {
		status_header( 404 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'This report card is not available for your account.';
		exit;
	}

	if ( ! mags_erp_report_card_fits_pdf( $card ) ) {
		status_header( 200 );
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		require MAGS_ERP_PATH . 'views/report-card.php';
		exit;
	}

	$pdf  = mags_erp_report_card_pdf( $card );
	$name = 'report-card-' . sanitize_title( $card['student']->admission_no . '-' . $card['exam'] ) . '.pdf';
	while ( ob_get_level() ) { ob_end_clean(); }
	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Length: ' . strlen( $pdf ) );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: attachment; filename="' . $name . '"' );
	echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput -- binary PDF.
	exit;
}
