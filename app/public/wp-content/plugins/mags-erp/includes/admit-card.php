<?php
/**
 * Date sheet and admit card of an exam.
 *
 * The date sheet is the list of papers of a class with their date and time.
 * The office shares it with the students (and their parents) when it is ready;
 * from then on they see it under More → Exams and can download their admit
 * card: a half A4 page with the photo, class, roll number and the papers.
 *
 * The office decides who gets an admit card: every student, only students
 * without an overdue fee, or only students who have paid all fees. On top of
 * that it can hold the card of one student or let one through anyway. A held
 * student sees why, and the office can still print the card at the office.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** rule => label of who gets an admit card. */
function mags_erp_admit_rules() {
	return array(
		'none'    => 'Every student',
		'overdue' => 'Only students with no overdue fee',
		'dues'    => 'Only students who have paid all fees',
	);
}

/** What is printed under the papers when the office has written nothing. */
function mags_erp_admit_default_note() {
	return 'Bring this admit card to every paper. Reach the school 20 minutes before the paper starts. Mobile phones are not allowed in the examination room.';
}

/** student_id => 'hold' or 'allow' for the students the office decided about by hand. */
function mags_erp_admit_overrides( $exam_id ) {
	global $wpdb;
	$states = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT student_id, state FROM ' . mags_erp_table( 'exam_admit' ) . ' WHERE exam_id = %d', $exam_id ) ) as $row ) { $states[ (int) $row->student_id ] = $row->state; }
	return $states;
}

/**
 * May this student have the admit card? Returns array( ok, state, why ): state is
 * ready, allowed (by the office, despite fees), fees (held by the fee rule) or
 * held (by the office). $overrides and $dues can be passed in for a whole class.
 */
function mags_erp_admit_status( $exam, $student, $overrides = null, $dues = null ) {
	$overrides = null === $overrides ? mags_erp_admit_overrides( (int) $exam->id ) : $overrides;
	$state     = $overrides[ (int) $student->id ] ?? '';
	if ( 'hold' === $state ) { return array( false, 'held', 'The school office has held this admit card. Please contact the office.' ); }
	if ( 'allow' === $state ) { return array( true, 'allowed', 'Allowed by the school office.' ); }
	if ( 'none' === $exam->admit_rule || '' === (string) $exam->admit_rule ) { return array( true, 'ready', '' ); }
	$dues = null === $dues ? mags_erp_fee_dues( array( (int) $student->id ) ) : $dues;
	$due  = $dues[ (int) $student->id ] ?? null;
	if ( 'overdue' === $exam->admit_rule && $due && $due['overdue'] > 0 ) { return array( false, 'fees', sprintf( 'The admit card is held because fees of %s are overdue. Please pay at the school office; the card can then be downloaded here.', mags_erp_money( $due['overdue'] ) ) ); }
	if ( 'dues' === $exam->admit_rule && $due && $due['left'] > 0 ) { return array( false, 'fees', sprintf( 'The admit card is held until all fees are paid. %s is left to pay. Please pay at the school office; the card can then be downloaded here.', mags_erp_money( $due['left'] ) ) ); }
	return array( true, 'ready', '' );
}

/** Exams whose date sheet is shared and whose results are not out, with papers for this class, soonest first. */
function mags_erp_class_datesheet_exams( $class_id ) {
	$exams = array();
	foreach ( mags_erp_get_exams() as $exam ) {
		if ( ! $exam->datesheet_at || $exam->published_at ) { continue; }
		$exam->papers = mags_erp_datesheet( (int) $exam->id, $class_id );
		if ( $exam->papers ) { $exams[] = $exam; }
	}
	return array_reverse( $exams );
}

/* ---------- What is printed ---------- */

/** One date sheet row for people: array( date, day, time, subject, out of ). */
function mags_erp_datesheet_row( $paper ) {
	return array(
		$paper->exam_date ? mags_erp_format_date( $paper->exam_date, 'j M Y' ) : 'Date to come',
		$paper->exam_date ? mags_erp_format_date( $paper->exam_date, 'l' ) : '',
		mags_erp_paper_time( $paper ),
		$paper->subject_name,
		mags_erp_format_marks( $paper->max_marks ),
	);
}

/** Everything on one student's admit card. */
function mags_erp_admit_card_data( $exam, $student, $papers, $labels ) {
	$profile = mags_erp_get_profile( (int) $student->id );
	$rows    = array_map( 'mags_erp_datesheet_row', $papers );
	return array(
		'school'    => mags_erp_school_details(),
		'exam'      => $exam->name,
		'session'   => mags_erp_session_range( $exam->start_date ?: mags_erp_today() )['label'],
		'name'      => $student->full_name,
		'class'     => $labels[ (int) $student->class_id ] ?? '',
		'roll'      => (string) $student->roll_no,
		'admission' => $student->admission_no,
		'parent'    => '' !== $profile->father_name ? $profile->father_name : $profile->mother_name,
		'photo'     => mags_erp_photo_path( $profile ),
		'photo_id'  => (int) $profile->photo_file_id,
		'rows'      => $rows,
		'note'      => '' !== trim( (string) $exam->admit_note ) ? trim( (string) $exam->admit_note ) : mags_erp_admit_default_note(),
	);
}

/** Height a card needs on the page, in points. */
function mags_erp_admit_card_height( $pdf, $card ) {
	return 252 + 15 * count( $card['rows'] ) + 10 * min( 4, count( $pdf->wrap( $card['note'], 7.5, false, 505 ) ) ) + 56;
}

/** Draw one admit card with its top edge at $top, $height tall. */
function mags_erp_admit_card_draw( $pdf, $card, $top, $height ) {
	$c     = mags_erp_pdf_colours();
	$left  = 45;
	$right = MAGS_ERP_PDF::WIDTH - 45;
	$mid   = MAGS_ERP_PDF::WIDTH / 2;
	$s     = $card['school'];

	$pdf->rect( 30, $top + 8, MAGS_ERP_PDF::WIDTH - 60, $height - 16, 0.5, array( 0.6, 0.62, 0.72 ), true );
	$pdf->box( 30, $top + 8, MAGS_ERP_PDF::WIDTH - 60, 46, $c['navy'] );
	$pdf->box( 30, $top + 54, MAGS_ERP_PDF::WIDTH - 60, 2, $c['orange'] );
	$pdf->text( $mid, $top + 29, $pdf->fit( $s['name'], 13, true, $right - $left ), 13, true, 'C', $c['white'] );
	$where = trim( $s['address'] . ( '' !== $s['phone'] ? ' · Phone: ' . $s['phone'] : '' ) );
	if ( '' !== $where ) { $pdf->text( $mid, $top + 44, $pdf->fit( $where, 7.5, false, $right - $left ), 7.5, false, 'C', array( 0.86, 0.87, 0.95 ) ); }

	$pdf->text( $mid, $top + 76, 'ADMIT CARD', 12, true, 'C', $c['navy'] );
	$pdf->text( $mid, $top + 92, $pdf->fit( $card['exam'] . ' · Session ' . $card['session'], 10, false, 380 ), 10, false, 'C', $c['navy'] );

	$pw = 72;
	$ph = 90;
	$px = $right - $pw;
	$py = $top + 104;
	if ( '' === $card['photo'] || ! $pdf->jpeg( $card['photo'], $px, $py, $pw, $ph ) ) {
		$pdf->box( $px, $py, $pw, $ph, $c['soft'] );
		$pdf->text( $px + $pw / 2, $py + $ph / 2 + 3, 'Photo', 8, false, 'C', $c['muted'] );
	}
	$pdf->rect( $px, $py, $pw, $ph, 0.8, $c['navy'] );

	$facts = array(
		array( $left, 116, 'NAME OF THE STUDENT', $card['name'], 380 ),
		array( $left, 144, 'CLASS', $card['class'], 180 ),
		array( 245, 144, 'ROLL NO.', '' !== $card['roll'] ? $card['roll'] : '-', 150 ),
		array( $left, 172, 'ADMISSION NO.', $card['admission'], 180 ),
		array( 245, 172, 'FATHER / MOTHER', '' !== $card['parent'] ? $card['parent'] : '-', 180 ),
	);
	foreach ( $facts as $fact ) {
		$pdf->text( $fact[0], $top + $fact[1], $fact[2], 6.5, true, 'L', $c['muted'] );
		$pdf->text( $fact[0], $top + $fact[1] + 12, $pdf->fit( $fact[3], 10.5, true, $fact[4] ), 10.5, true, 'L', $c['navy'] );
	}

	$cols = array( $left + 8, $left + 100, $left + 175, $left + 300 );
	$y    = $top + 208;
	$pdf->box( $left, $y, $right - $left, 18, $c['navy'] );
	foreach ( array( 'Date', 'Day', 'Time', 'Subject' ) as $index => $head ) { $pdf->text( $cols[ $index ], $y + 12.5, $head, 8.5, true, 'L', $c['white'] ); }
	$y += 18;
	foreach ( $card['rows'] as $index => $row ) {
		if ( 1 === $index % 2 ) { $pdf->box( $left, $y, $right - $left, 15, array( 0.97, 0.975, 0.995 ) ); }
		$pdf->text( $cols[0], $y + 11, $row[0], 9, true, 'L', $c['navy'] );
		$pdf->text( $cols[1], $y + 11, $row[1], 9, false, 'L', $c['navy'] );
		$pdf->text( $cols[2], $y + 11, $pdf->fit( '' !== $row[2] ? $row[2] : '-', 9, false, 120 ), 9, false, 'L', $c['navy'] );
		$pdf->text( $cols[3], $y + 11, $pdf->fit( $row[3], 9, true, $right - $cols[3] - 6 ), 9, true, 'L', $c['navy'] );
		$y += 15;
	}
	$pdf->line( $left, $y, $right, $y, 0.6, $c['line'] );
	$y += 14;
	foreach ( array_slice( $pdf->wrap( $card['note'], 7.5, false, $right - $left ), 0, 4 ) as $line ) {
		$pdf->text( $left, $y, $line, 7.5, false, 'L', $c['muted'] );
		$y += 10;
	}
	mags_erp_pdf_signatures( $pdf, $top + $height - 32, array( 'Class teacher', 'Principal' ), $left, $right );
}

/** The admit cards as one PDF: two to a page when they fit, otherwise one. */
function mags_erp_admit_cards_pdf( $cards, $title ) {
	$pdf  = new MAGS_ERP_PDF();
	$half = MAGS_ERP_PDF::HEIGHT / 2;
	$slot = 0;
	foreach ( $cards as $card ) {
		$need = mags_erp_admit_card_height( $pdf, $card );
		if ( $need > $half - 4 ) {
			$pdf->add_page();
			mags_erp_admit_card_draw( $pdf, $card, 10, max( $need, 400 ) );
			$slot = 0;
			continue;
		}
		if ( 0 === $slot ) { $pdf->add_page(); }
		mags_erp_admit_card_draw( $pdf, $card, $slot * $half, $half );
		$slot = ( $slot + 1 ) % 2;
	}
	return $pdf->output( $title );
}

/** The date sheet of one class as a PDF. */
function mags_erp_datesheet_pdf( $exam, $class, $papers ) {
	$c     = mags_erp_pdf_colours();
	$left  = 50;
	$right = MAGS_ERP_PDF::WIDTH - 50;
	$cols  = array( $left + 10, $left + 110, $left + 190, $left + 310, $right - 10 );
	$pdf   = new MAGS_ERP_PDF();
	$pdf->add_page();
	$y = mags_erp_pdf_letterhead( $pdf, mags_erp_school_details(), 'DATE SHEET' ) + 6;
	$pdf->text( MAGS_ERP_PDF::WIDTH / 2, $y, $pdf->fit( $exam->name . ' · ' . mags_erp_class_label( $class ), 12, true, $right - $left ), 12, true, 'C', $c['navy'] );
	$y += 24;
	$head = static function ( $y ) use ( $pdf, $c, $left, $right, $cols ) {
		$pdf->box( $left, $y, $right - $left, 22, $c['navy'] );
		foreach ( array( 'Date', 'Day', 'Time', 'Subject' ) as $index => $text ) { $pdf->text( $cols[ $index ], $y + 15, $text, 10, true, 'L', $c['white'] ); }
		$pdf->text( $cols[4], $y + 15, 'Max. marks', 10, true, 'R', $c['white'] );
		return $y + 22;
	};
	$y = $head( $y );
	foreach ( $papers as $index => $paper ) {
		if ( $y + 22 > 770 ) {
			$pdf->add_page();
			$y = $head( 60 );
		}
		$row = mags_erp_datesheet_row( $paper );
		if ( 1 === $index % 2 ) { $pdf->box( $left, $y, $right - $left, 22, array( 0.97, 0.975, 0.995 ) ); }
		$pdf->text( $cols[0], $y + 15, $row[0], 10.5, true, 'L', $c['navy'] );
		$pdf->text( $cols[1], $y + 15, $row[1], 10.5, false, 'L', $c['navy'] );
		$pdf->text( $cols[2], $y + 15, $pdf->fit( '' !== $row[2] ? $row[2] : '-', 10.5, false, 115 ), 10.5, false, 'L', $c['navy'] );
		$pdf->text( $cols[3], $y + 15, $pdf->fit( $row[3], 10.5, true, $cols[4] - $cols[3] - 50 ), 10.5, true, 'L', $c['navy'] );
		$pdf->text( $cols[4], $y + 15, $row[4], 10.5, false, 'R', $c['navy'] );
		$pdf->line( $left, $y + 22, $right, $y + 22 );
		$y += 22;
	}
	$y += 26;
	$note = '' !== trim( (string) $exam->admit_note ) ? trim( (string) $exam->admit_note ) : mags_erp_admit_default_note();
	foreach ( $pdf->wrap( $note, 9.5, false, $right - $left ) as $line ) {
		$pdf->text( $left, $y, $line, 9.5, false, 'L', $c['muted'] );
		$y += 13;
	}
	mags_erp_pdf_signatures( $pdf, max( $y + 60, 700 ), array( 'Principal' ), $left, $right );
	$pdf->text( MAGS_ERP_PDF::WIDTH / 2, 806, 'Made from the school portal on ' . mags_erp_format_date( mags_erp_today() ) . '.', 8, false, 'C', $c['muted'] );
	return $pdf->output( 'Date sheet - ' . $exam->name . ' - ' . mags_erp_class_label( $class ) );
}

/* ---------- Downloads ---------- */

/**
 * /portal/date-sheet/?exam=…&class=… : the office for any class, a teacher for
 * their own classes, a student for their own class once the date sheet is shared.
 */
function mags_erp_serve_datesheet() {
	$role  = mags_erp_user_role();
	$exam  = mags_erp_get_exam( mags_erp_get_int( 'exam' ) );
	$class = mags_erp_get_class( mags_erp_get_int( 'class' ) );
	if ( 'student' === $role ) {
		$student = mags_erp_get_student_by_user( get_current_user_id() );
		$class   = $student ? mags_erp_get_class( (int) $student->class_id ) : null;
		$exam    = $exam && $exam->datesheet_at ? $exam : null;
	} elseif ( 'teacher' === $role ) {
		$class = $class && mags_erp_teacher_has_class( get_current_user_id(), (int) $class->id ) ? $class : null;
	} elseif ( 'office' !== $role ) {
		$class = null;
	}
	$papers = $exam && $class ? mags_erp_datesheet( (int) $exam->id, (int) $class->id ) : array();
	if ( ! $papers ) { mags_erp_download_refused( 'This date sheet is not available.' ); }

	$rows = array_map( 'mags_erp_datesheet_row', $papers );
	if ( ! mags_erp_pdf_can_write_all( array( $exam->name, mags_erp_class_label( $class ), $rows, (string) $exam->admit_note, mags_erp_school_details() ) ) ) {
		$lines = array();
		foreach ( $rows as $row ) { $lines[] = array( trim( $row[0] . ' ' . $row[1] . ' ' . $row[2] ), $row[3] . ' (' . $row[4] . ')' ); }
		mags_erp_print_documents( array( array( 'title' => 'DATE SHEET', 'serial' => $exam->name . ' · ' . mags_erp_class_label( $class ), 'rows' => $lines, 'note' => '' !== trim( (string) $exam->admit_note ) ? $exam->admit_note : mags_erp_admit_default_note() ) ), 'Date sheet ' . $exam->name, 'student' === $role ? mags_erp_url( 'exams' ) : mags_erp_url( 'teacher' === $role ? 'marks' : 'exams', array( 'id' => 'office' === $role ? (int) $exam->id : '' ) ) );
	}
	mags_erp_send_pdf( mags_erp_datesheet_pdf( $exam, $class, $papers ), 'date-sheet-' . sanitize_title( $exam->name . '-' . mags_erp_class_label( $class ) ) . '.pdf' );
}

/**
 * /portal/admit-card/?exam=… : a student gets their own card when the date
 * sheet is shared and the card is not held. The office gets any student's card
 * (&student=…), held or not, or the cards of a class (&class=…) that are not held.
 */
function mags_erp_serve_admit_card() {
	$role = mags_erp_user_role();
	$exam = mags_erp_get_exam( mags_erp_get_int( 'exam' ) );
	if ( ! $exam ) { mags_erp_download_refused( 'This admit card is not available.' ); }
	$labels   = mags_erp_all_class_labels();
	$students = array();
	$back     = mags_erp_url( 'exams' );
	if ( 'student' === $role ) {
		$student = mags_erp_get_student_by_user( get_current_user_id() );
		if ( ! $student || ! $exam->datesheet_at ) { mags_erp_download_refused( 'This admit card is not available yet.' ); }
		list( $ok, , $why ) = mags_erp_admit_status( $exam, $student );
		if ( ! $ok ) { mags_erp_download_refused( $why ); }
		$students = array( $student );
	} elseif ( 'office' === $role ) {
		$class = mags_erp_get_class( mags_erp_get_int( 'class' ) );
		if ( $class ) {
			$list      = mags_erp_class_students( (int) $class->id );
			$overrides = mags_erp_admit_overrides( (int) $exam->id );
			$dues      = mags_erp_fee_dues( wp_list_pluck( $list, 'id' ) );
			foreach ( $list as $one ) { if ( mags_erp_admit_status( $exam, $one, $overrides, $dues )[0] ) { $students[] = $one; } }
			$back = mags_erp_url( 'admit-cards', array( 'exam' => (int) $exam->id, 'class' => (int) $class->id ) );
		} else {
			$one      = mags_erp_get_student( mags_erp_get_int( 'student' ) );
			$students = $one && ! $one->hidden_at ? array( $one ) : array();
			$back     = $one ? mags_erp_url( 'admit-cards', array( 'exam' => (int) $exam->id, 'class' => (int) $one->class_id ) ) : $back;
		}
	}
	$cards = array();
	foreach ( $students as $student ) {
		$papers = mags_erp_datesheet( (int) $exam->id, (int) $student->class_id );
		if ( $papers ) { $cards[] = mags_erp_admit_card_data( $exam, $student, $papers, $labels ); }
	}
	if ( ! $cards ) { mags_erp_download_refused( 'There is no admit card to make: the exam has no papers for this class, or every card is held.' ); }

	$title = 'Admit card' . ( 1 === count( $cards ) ? ' - ' . $cards[0]['name'] : 's' ) . ' - ' . $exam->name;
	$texts = $cards;
	foreach ( $texts as $index => $card ) { unset( $texts[ $index ]['photo'] ); }
	if ( ! mags_erp_pdf_can_write_all( $texts ) ) {
		$documents = array();
		foreach ( $cards as $card ) {
			$rows = array();
			foreach ( $card['rows'] as $row ) { $rows[] = array( trim( $row[0] . ' ' . $row[1] . ' ' . $row[2] ), $row[3] ); }
			$documents[] = array( 'title' => 'ADMIT CARD', 'serial' => $card['exam'] . ' · Session ' . $card['session'], 'photo' => $card['photo_id'], 'facts' => array( 'Name' => $card['name'], 'Class' => $card['class'], 'Roll no.' => $card['roll'], 'Admission no.' => $card['admission'], 'Father / Mother' => $card['parent'] ), 'rows' => $rows, 'note' => $card['note'], 'signatures' => array( 'Class teacher', 'Principal' ) );
		}
		mags_erp_print_documents( $documents, $title, $back );
	}
	mags_erp_send_pdf( mags_erp_admit_cards_pdf( $cards, $title ), sanitize_title( 1 === count( $cards ) ? 'admit-card-' . $students[0]->admission_no . '-' . $exam->name : 'admit-cards-' . $exam->name ) . '.pdf' );
}

/* ---------- Office actions ---------- */

function mags_erp_action_save_admit_settings() {
	global $wpdb;
	$exam = mags_erp_get_exam( mags_erp_post_int( 'exam_id' ) );
	if ( ! $exam ) { mags_erp_flash( 'error', 'That exam no longer exists.' ); return mags_erp_url( 'exams' ); }
	$back  = mags_erp_url( 'exams', array( 'id' => (int) $exam->id ) ) . '#erp-admit';
	$rule  = mags_erp_post_text( 'admit_rule' );
	$note  = mags_erp_post_textarea( 'admit_note', 600 );
	$share = (bool) mags_erp_post_int( 'share' );
	if ( ! isset( mags_erp_admit_rules()[ $rule ] ) ) { mags_erp_flash( 'error', 'Choose who gets an admit card.' ); return $back; }
	if ( $share && ! mags_erp_exam_papers( (int) $exam->id ) ) { mags_erp_flash( 'error', 'Add the papers of the exam before sharing the date sheet.' ); return $back; }
	$wpdb->update( mags_erp_table( 'exams' ), array( 'admit_rule' => $rule, 'admit_note' => $note, 'datesheet_at' => $share ? ( $exam->datesheet_at ?: current_time( 'mysql' ) ) : null ), array( 'id' => (int) $exam->id ), array( '%s', '%s', '%s' ), array( '%d' ) );
	mags_erp_flash( 'success', $share ? 'Saved. Students see the date sheet under More → Exams and can download their admit card (' . strtolower( mags_erp_admit_rules()[ $rule ] ) . ').' : 'Saved. The date sheet and admit cards are not shown to students.' );
	return $back;
}

/** Hold one student's admit card, let it through despite the fee rule, or go back to the rule (state ''). */
function mags_erp_action_set_admit() {
	global $wpdb;
	$exam    = mags_erp_get_exam( mags_erp_post_int( 'exam_id' ) );
	$student = mags_erp_get_student( mags_erp_post_int( 'student_id' ) );
	$state   = mags_erp_post_text( 'state' );
	if ( ! $exam || ! $student ) { mags_erp_flash( 'error', 'That exam or student no longer exists.' ); return mags_erp_url( 'exams' ); }
	$back  = mags_erp_url( 'admit-cards', array( 'exam' => (int) $exam->id, 'class' => (int) $student->class_id ) );
	$state = in_array( $state, array( 'hold', 'allow' ), true ) ? $state : '';
	$table = mags_erp_table( 'exam_admit' );
	$data  = array( 'state' => $state, 'set_by' => get_current_user_id(), 'set_at' => current_time( 'mysql' ) );
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT student_id FROM {$table} WHERE exam_id = %d AND student_id = %d", $exam->id, $student->id ) ) ) {
		$wpdb->update( $table, $data, array( 'exam_id' => (int) $exam->id, 'student_id' => (int) $student->id ), array( '%s', '%d', '%s' ), array( '%d', '%d' ) );
	} else {
		$wpdb->insert( $table, array_merge( $data, array( 'exam_id' => (int) $exam->id, 'student_id' => (int) $student->id ) ), array( '%s', '%d', '%s', '%d', '%d' ) );
	}
	$messages = array( 'hold' => 'The admit card of %s is held.', 'allow' => 'The admit card of %s is allowed, whatever the fees.', '' => 'The admit card of %s follows the rule of the exam again.' );
	mags_erp_flash( 'success', sprintf( $messages[ $state ], $student->full_name ) );
	return $back;
}
