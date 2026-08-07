<?php
/**
 * config/constants.php
 * SOPRA — app-wide constants: branding, ranks, months, duty types,
 * and the Malaysia state/district list used by the duty form.
 */

const APP_NAME       = 'SOPRA';
const APP_FULL_NAME  = 'System for Operational Personnel Resource Allocation';
const APP_UNIT       = 'PASUKAN TINDAKAN KHAS — N.C.I.D';

const RANKS  = ["DSP","ASP","INSP","SI","SI/D","SM","SM/D","SJN","SJN/D","KPL","KPL/D","LANS KPL","LANS KPL/D","KONST"];
const MONTHS = ["JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC"];
const MONTHS_FULL = ["January","February","March","April","May","June","July","August","September","October","November","December"];

// ---------------------------------------------------------------
// Duty assignment types (Tugasan). Keys are stored in the DB
// (duty_assignments.duty_type), values are the English labels shown
// in the UI, filters, and CSV export.
// ---------------------------------------------------------------
const DUTY_TYPES = [
    'CONFIDENTIAL'  => 'Confidential',
    'COURT_HEARING' => 'Court Hearing',
    'LDP'           => 'In-Service Training (LDP)',
    'EXHIBITION'    => 'Exhibition',
    'OTHER'         => 'Other',
];

// ---------------------------------------------------------------
// Malaysia states -> districts, for the cascading State/District
// picker on the Record Duty form. Edit/extend this list if your
// unit's operational area needs different or additional districts.
// ---------------------------------------------------------------
const STATES_DISTRICTS = [
    'Johor' => ['Batu Pahat','Johor Bahru','Kluang','Kota Tinggi','Kulai','Mersing','Muar','Pontian','Segamat','Tangkak'],
    'Kedah' => ['Baling','Bandar Baharu','Kota Setar','Kuala Muda','Kubang Pasu','Kulim','Langkawi','Padang Terap','Pendang','Pokok Sena','Sik','Yan'],
    'Kelantan' => ['Bachok','Gua Musang','Jeli','Kota Bharu','Kuala Krai','Machang','Pasir Mas','Pasir Puteh','Tanah Merah','Tumpat'],
    'Melaka' => ['Alor Gajah','Jasin','Melaka Tengah'],
    'Negeri Sembilan' => ['Jelebu','Jempol','Kuala Pilah','Port Dickson','Rembau','Seremban','Tampin'],
    'Pahang' => ['Bentong','Bera','Cameron Highlands','Jerantut','Kuantan','Lipis','Maran','Pekan','Raub','Rompin','Temerloh'],
    'Perak' => ['Bagan Datuk','Batang Padang','Hilir Perak','Hulu Perak','Kerian','Kinta','Kuala Kangsar','Larut Matang & Selama','Manjung','Muallim','Perak Tengah'],
    'Perlis' => ['Perlis'],
    'Pulau Pinang' => ['Barat Daya','Seberang Perai Selatan','Seberang Perai Tengah','Seberang Perai Utara','Timur Laut'],
    'Sabah' => ['Beaufort','Beluran','Keningau','Kota Belud','Kota Kinabalu','Kota Marudu','Kudat','Kunak','Lahad Datu','Papar','Penampang','Ranau','Sandakan','Semporna','Tawau','Tuaran'],
    'Sarawak' => ['Bintulu','Kapit','Kuching','Limbang','Miri','Mukah','Samarahan','Sarikei','Sri Aman','Sibu'],
    'Selangor' => ['Gombak','Hulu Langat','Hulu Selangor','Klang','Kuala Langat','Kuala Selangor','Petaling','Sabak Bernam','Sepang'],
    'Terengganu' => ['Besut','Dungun','Hulu Terengganu','Kemaman','Kuala Nerus','Kuala Terengganu','Marang','Setiu'],
    'W.P. Kuala Lumpur' => ['Kuala Lumpur'],
    'W.P. Labuan' => ['Labuan'],
    'W.P. Putrajaya' => ['Putrajaya'],
];
