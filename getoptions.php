<?php
/*   __________________________________________________
    |              on 2.0.12              |
    |__________________________________________________|
*/
 goto nW5uc; hQSlu: redirect($p2gtq); goto BJGei; rLUNu: require_once $CFG->dirroot . "\57" . $j3CFP . "\57\160\x72\157\57\146\157\x72\155\x73\x2f\x66\x6f\162\x6d\x5f\x67\x65\x74\153\x65\171\56\x70\150\160"; goto CFdez; BGLvc: if (!$dTOAZ) { goto nAw7R; } goto TqRvJ; MPQjZ: $OL2Cm = local_my\pro_manager::instance(); goto a7m8o; CFdez: $Nw3aH = new moodle_url("\57" . $j3CFP . "\x2f\160\162\157\x2f\147\145\164\157\160\164\x69\x6f\x6e\163\56\160\150\x70"); goto kzGUg; tkwM6: require_capability("\x6d\x6f\157\x64\154\x65\57\163\x69\x74\145\72\143\157\x6e\x66\151\x67", $rK50J); goto nOsQn; BJGei: nAw7R: goto hJuj0; IM1ht: $PAGE->set_context($rK50J); goto f_Ofe; kzGUg: $PAGE->set_url($Nw3aH); goto muDyb; jiznB: redirect($OL2Cm->return_url()); goto LCgje; LCgje: LQQFv: goto fziYF; f_Ofe: require_login(); goto tkwM6; nOsQn: $OL2Cm->require_pro(); goto tK0id; fziYF: $dTOAZ = $PQ6Yn->get_data(); goto BGLvc; a7m8o: $j3CFP = $OL2Cm::$componentpath; goto rLUNu; TI8KJ: $PQ6Yn->display(); goto orc3H; Spris: require_once $CFG->dirroot . "\x2f\x6c\157\143\141\154\x2f\x6d\x79\x2f\x70\x72\157\x2f\x70\x72\157\154\x69\142\x2e\160\150\160"; goto MPQjZ; nW5uc: include "\56\56\57\x2e\56\57\x2e\56\x2f\x63\x6f\156\146\x69\x67\x2e\x70\150\x70"; goto Dv2WU; g1Aaw: $p2gtq = new moodle_url("\57" . $j3CFP . "\x2f\x70\x72\x6f\x2f\x67\x65\164\153\145\x79\x2e\160\150\x70", $SJjpI); goto hQSlu; P9hfz: if (!$PQ6Yn->is_cancelled()) { goto LQQFv; } goto jiznB; muDyb: $rK50J = context_system::instance(); goto IM1ht; hJuj0: echo $OUTPUT->header(); goto TI8KJ; TqRvJ: $SJjpI = ["\160\162\x6f\x76\x69\x64\145\x72" => $dTOAZ->provider, "\x70\x61\x72\164\x6e\145\162\153\x65\x79" => $dTOAZ->partnerkey]; goto g1Aaw; tK0id: $PQ6Yn = new GetKeyStart_Form($Nw3aH, ["\x6d\x61\156\141\x67\x65\162" => $OL2Cm]); goto P9hfz; Dv2WU: require_once $CFG->dirroot . "\57\154\157\143\141\154\57\x6d\x79\x2f\x6c\151\x62\x2e\x70\150\x70"; goto Spris; orc3H: echo $OUTPUT->footer();