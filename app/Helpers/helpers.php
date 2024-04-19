<?php

use App\Models\KycData;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Settings;
use App\Mail\EmailMessages;
use App\Models\Announcement;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;
use App\Http\Controllers\WalletController;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Route;

if (!function_exists("logEmails")) {
    function logEmails($email_to, $subject, $body)
    {
        try {
            EmailLog::create([
                'status' => 'pending',
                'subject' => $subject,
                'recipient' => $email_to,
                'content' => $body,
            ]);
           
        } catch (\Exception $e) {}
    }
}

// if (!function_exists("sendEmail")) {
//     function sendEmail($count, $single = false)
//     {
//         if ($single){
//             $pendingMails = EmailLog::where('id', 'pending')->take($count);
//         } else {
//             $pendingMails = EmailLog::where('status', 'pending')->take($count);
//         }


//         if ($pendingMails) {
//             $clonePendingMail = clone $pendingMails;
//             foreach (array_chunk($clonePendingMail->toArray(), 100) as $key => $value) {
//                 $current = $pendingMails[$key];
//                 sendEmailObject($current);
//             }
//         }
//     }
// }

// if (!function_exists('sendEmailObject')) {
//     function sendEmailObject ($current) {
//         try {
//             Mail::to($current->recipient)->send(new EmailMessages([
//                 'subject' => $current->subject,
//                 'body' => $current->content,
//             ]));

//             $current->status = 'sent';
//             $current->save();
//             return true;
//         } catch (\Throwable $th) {
//             $current->status = 'failed';
//             $current->save();
//             return false;
//         }
//     }
// }


if (!function_exists("sendEmails")) {
    function sendEmails($email_to, $subject, $body)
    {
        $data = [
            'subject' => $subject,
            'body' => $body,
        ];

        try {

            Mail::to($email_to)->send(new EmailMessages($data));
            return true;
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
        }
    }
}


if (!function_exists("getUniqueElements")) {
    function getUniqueElements()
    {
        return [
            'phone',
            'meter_number',
            'iuc_number',
            'account_id'
        ];
    }
}

if (!function_exists("verifiableUniqueElements")) {
    function verifiableUniqueElements()
    {
        return ['meter_number', 'iuc_number', 'profile_id'];
    }
}

if (!function_exists("getCategories")) {
    function getCategories()
    {
        return Category::where('status', 'active')->where('type', 'general')->orderBy('order', 'ASC')->get();
    }
}

if (!function_exists("walletBalance")) {
    function walletBalance($user)
    {
        $wallet = new WalletController();
        return $wallet->getWalletBalance($user);
    }
}

if (!function_exists("referralBalance")) {
    function referralBalance($user)
    {
        $wallet = new WalletController();
        return $wallet->getReferralBalance($user);
    }
}

if (!function_exists("airtime2cashBalance")) {
    function airtime2cashBalance($user)
    {
        $balance = new WalletController();
        return $balance->airtime2cashBalance($user);
    }
}

if (!function_exists("getSettings")) {
    function getSettings()
    {
        return Settings::first();
    }
}

if (!function_exists("staffDefaultPassword")) {
    function staffDefaultPassword()
    {
        return '550523';
    }
}

if (!function_exists("specialVerifiableVariations")) {
    function specialVerifiableVariations()
    {
        return $specialVerifiableVariations = [
            'utme-no-mock' => 'profile_id',
            'utme-mock' => 'profile_id',
            'de' => 'profile_id'
        ];
    }
}


if (!function_exists("getStates")) {
    function getStates()
    {
        $states = [
            "Abia",
            "Adamawa",
            "Akwa Ibom",
            "Anambra",
            "Bauchi",
            "Bayelsa",
            "Benue",
            "Borno",
            "Cross River",
            "Delta",
            "Ebonyi",
            "Edo",
            "Ekiti",
            "Enugu",
            "FCT - Abuja",
            "Gombe",
            "Imo",
            "Jigawa",
            "Kaduna",
            "Kano",
            "Katsina",
            "Kebbi",
            "Kogi",
            "Kwara",
            "Lagos",
            "Nasarawa",
            "Niger",
            "Ogun",
            "Ondo",
            "Osun",
            "Oyo",
            "Plateau",
            "Rivers",
            "Sokoto",
            "Taraba",
            "Yobe",
            "Zamfara"
        ];

        return $states;
    }
}

if (!function_exists("getLgas")) {
    function getLgas($state = null)
    {
        $states = [
            [
                "state" => "Adamawa",
                "alias" => "adamawa",
                "lgas" => [
                    "Demsa",
                    "Fufure",
                    "Ganye",
                    "Gayuk",
                    "Gombi",
                    "Grie",
                    "Hong",
                    "Jada",
                    "Larmurde",
                    "Madagali",
                    "Maiha",
                    "Mayo Belwa",
                    "Michika",
                    "Mubi North",
                    "Mubi South",
                    "Numan",
                    "Shelleng",
                    "Song",
                    "Toungo",
                    "Yola North",
                    "Yola South"
                ]
            ],

            [
                "state" => "FCT - Abuja",
                "alias" => "abuja",
                "lgas" => [
                    "Abaji LGA",
                    "Abuja Municipal Area Council",
                    "Bwari LGA",
                    "Gwagwalada LGA",
                    "Kwali LGA"
                ]
            ],

            [
                "state" => "Akwa Ibom",
                "alias" => "akwa_ibom",
                "lgas" => [
                    "Abak",
                    "Eastern Obolo",
                    "Eket",
                    "Esit Eket",
                    "Essien Udim",
                    "Etim Ekpo",
                    "Etinan",
                    "Ibeno",
                    "Ibesikpo Asutan",
                    "Ibiono-Ibom",
                    "Ikot Abasi",
                    "Ika",
                    "Ikono",
                    "Ikot Ekpene",
                    "Ini",
                    "Mkpat-Enin",
                    "Itu",
                    "Mbo",
                    "Nsit-Atai",
                    "Nsit-Ibom",
                    "Nsit-Ubium",
                    "Obot Akara",
                    "Okobo",
                    "Onna",
                    "Oron",
                    "Udung-Uko",
                    "Ukanafun",
                    "Oruk Anam",
                    "Uruan",
                    "Urue-Offong/Oruko",
                    "Uyo"
                ]
            ],
            [
                "state" => "Anambra",
                "alias" => "anambra",
                "lgas" => [
                    "Aguata",
                    "Anambra East",
                    "Anaocha",
                    "Awka North",
                    "Anambra West",
                    "Awka South",
                    "Ayamelum",
                    "Dunukofia",
                    "Ekwusigo",
                    "Idemili North",
                    "Idemili South",
                    "Ihiala",
                    "Njikoka",
                    "Nnewi North",
                    "Nnewi South",
                    "Ogbaru",
                    "Onitsha North",
                    "Onitsha South",
                    "Orumba North",
                    "Orumba South",
                    "Oyi"
                ]
            ],
            [
                "state" => "Ogun",
                "alias" => "ogun",
                "lgas" => [
                    "Abeokuta North",
                    "Abeokuta South",
                    "Ado-Odo/Ota",
                    "Egbado North",
                    "Ewekoro",
                    "Egbado South",
                    "Ijebu North",
                    "Ijebu East",
                    "Ifo",
                    "Ijebu Ode",
                    "Ijebu North East",
                    "Imeko Afon",
                    "Ikenne",
                    "Ipokia",
                    "Odeda",
                    "Obafemi Owode",
                    "Odogbolu",
                    "Remo North",
                    "Ogun Waterside",
                    "Shagamu"
                ]
            ],
            [
                "state" => "Ondo",
                "alias" => "ondo",
                "lgas" => [
                    "Akoko North-East",
                    "Akoko North-West",
                    "Akoko South-West",
                    "Akoko South-East",
                    "Akure North",
                    "Akure South",
                    "Ese Odo",
                    "Idanre",
                    "Ifedore",
                    "Ilaje",
                    "Irele",
                    "Ile Oluji/Okeigbo",
                    "Odigbo",
                    "Okitipupa",
                    "Ondo West",
                    "Ose",
                    "Ondo East",
                    "Owo"
                ]
            ],
            [
                "state" => "Rivers",
                "alias" => "rivers",
                "lgas" => [
                    "Abua/Odual",
                    "Ahoada East",
                    "Ahoada West",
                    "Andoni",
                    "Akuku-Toru",
                    "Asari-Toru",
                    "Bonny",
                    "Degema",
                    "Emuoha",
                    "Eleme",
                    "Ikwerre",
                    "Etche",
                    "Gokana",
                    "Khana",
                    "Obio/Akpor",
                    "Ogba/Egbema/Ndoni",
                    "Ogu/Bolo",
                    "Okrika",
                    "Omuma",
                    "Opobo/Nkoro",
                    "Oyigbo",
                    "Port Harcourt",
                    "Tai"
                ]
            ],
            [
                "state" => "Bauchi",
                "alias" => "bauchi",
                "lgas" => [
                    "Alkaleri",
                    "Bauchi",
                    "Bogoro",
                    "Damban",
                    "Darazo",
                    "Dass",
                    "Gamawa",
                    "Ganjuwa",
                    "Giade",
                    "Itas/Gadau",
                    "Jama'are",
                    "Katagum",
                    "Kirfi",
                    "Misau",
                    "Ningi",
                    "Shira",
                    "Tafawa Balewa",
                    "Toro",
                    "Warji",
                    "Zaki"
                ]
            ],
            [
                "state" => "Benue",
                "alias" => "benue",
                "lgas" => [
                    "Agatu",
                    "Apa",
                    "Ado",
                    "Buruku",
                    "Gboko",
                    "Guma",
                    "Gwer East",
                    "Gwer West",
                    "Katsina-Ala",
                    "Konshisha",
                    "Kwande",
                    "Logo",
                    "Makurdi",
                    "Obi",
                    "Ogbadibo",
                    "Ohimini",
                    "Oju",
                    "Okpokwu",
                    "Oturkpo",
                    "Tarka",
                    "Ukum",
                    "Ushongo",
                    "Vandeikya"
                ]
            ],
            [
                "state" => "Bornu",
                "alias" => "bornu",
                "lgas" => [
                    "Abadam",
                    "Askira/Uba",
                    "Bama",
                    "Bayo",
                    "Biu",
                    "Chibok",
                    "Damboa",
                    "Dikwa",
                    "Guzamala",
                    "Gubio",
                    "Hawul",
                    "Gwoza",
                    "Jere",
                    "Kaga",
                    "Kala/Balge",
                    "Konduga",
                    "Kukawa",
                    "Kwaya Kusar",
                    "Mafa",
                    "Magumeri",
                    "Maiduguri",
                    "Mobbar",
                    "Marte",
                    "Monguno",
                    "Ngala",
                    "Nganzai",
                    "Shani"
                ]
            ],
            [
                "state" => "Bayelsa",
                "alias" => "bayelsa",
                "lgas" => [
                    "Brass",
                    "Ekeremor",
                    "Kolokuma/Opokuma",
                    "Nembe",
                    "Ogbia",
                    "Sagbama",
                    "Southern Ijaw",
                    "Yenagoa"
                ]
            ],
            [
                "state" => "Cross River",
                "alias" => "cross_river",
                "lgas" => [
                    "Abi",
                    "Akamkpa",
                    "Akpabuyo",
                    "Bakassi",
                    "Bekwarra",
                    "Biase",
                    "Boki",
                    "Calabar Municipal",
                    "Calabar South",
                    "Etung",
                    "Ikom",
                    "Obanliku",
                    "Obubra",
                    "Obudu",
                    "Odukpani",
                    "Ogoja",
                    "Yakuur",
                    "Yala"
                ]
            ],
            [
                "state" => "Delta",
                "alias" => "delta",
                "lgas" => [
                    "Aniocha North",
                    "Aniocha South",
                    "Bomadi",
                    "Burutu",
                    "Ethiope West",
                    "Ethiope East",
                    "Ika North East",
                    "Ika South",
                    "Isoko North",
                    "Isoko South",
                    "Ndokwa East",
                    "Ndokwa West",
                    "Okpe",
                    "Oshimili North",
                    "Oshimili South",
                    "Patani",
                    "Sapele",
                    "Udu",
                    "Ughelli North",
                    "Ukwuani",
                    "Ughelli South",
                    "Uvwie",
                    "Warri North",
                    "Warri South",
                    "Warri South West"
                ]
            ],
            [
                "state" => "Ebonyi",
                "alias" => "ebonyi",
                "lgas" => [
                    "Abakaliki",
                    "Afikpo North",
                    "Ebonyi",
                    "Afikpo South",
                    "Ezza North",
                    "Ikwo",
                    "Ezza South",
                    "Ivo",
                    "Ishielu",
                    "Izzi",
                    "Ohaozara",
                    "Ohaukwu",
                    "Onicha"
                ]
            ],
            [
                "state" => "Edo",
                "alias" => "edo",
                "lgas" => [
                    "Akoko-Edo",
                    "Egor",
                    "Esan Central",
                    "Esan North-East",
                    "Esan South-East",
                    "Esan West",
                    "Etsako Central",
                    "Etsako East",
                    "Etsako West",
                    "Igueben",
                    "Ikpoba Okha",
                    "Orhionmwon",
                    "Oredo",
                    "Ovia North-East",
                    "Ovia South-West",
                    "Owan East",
                    "Owan West",
                    "Uhunmwonde"
                ]
            ],
            [
                "state" => "Ekiti",
                "alias" => "ekiti",
                "lgas" => [
                    "Ado Ekiti",
                    "Efon",
                    "Ekiti East",
                    "Ekiti South-West",
                    "Ekiti West",
                    "Emure",
                    "Gbonyin",
                    "Ido Osi",
                    "Ijero",
                    "Ikere",
                    "Ilejemeje",
                    "Irepodun/Ifelodun",
                    "Ikole",
                    "Ise/Orun",
                    "Moba",
                    "Oye"
                ]
            ],
            [
                "state" => "Enugu",
                "alias" => "enugu",
                "lgas" => [
                    "Awgu",
                    "Aninri",
                    "Enugu East",
                    "Enugu North",
                    "Ezeagu",
                    "Enugu South",
                    "Igbo Etiti",
                    "Igbo Eze North",
                    "Igbo Eze South",
                    "Isi Uzo",
                    "Nkanu East",
                    "Nkanu West",
                    "Nsukka",
                    "Udenu",
                    "Oji River",
                    "Uzo Uwani",
                    "Udi"
                ]
            ],
            [
                "state" => "Federal Capital Territory",
                "alias" => "abuja",
                "lgas" => [
                    "Abaji",
                    "Bwari",
                    "Gwagwalada",
                    "Kuje",
                    "Kwali",
                    "Municipal Area Council"
                ]
            ],
            [
                "state" => "Gombe",
                "alias" => "gombe",
                "lgas" => [
                    "Akko",
                    "Balanga",
                    "Billiri",
                    "Dukku",
                    "Funakaye",
                    "Gombe",
                    "Kaltungo",
                    "Kwami",
                    "Nafada",
                    "Shongom",
                    "Yamaltu/Deba"
                ]
            ],
            [
                "state" => "Jigawa",
                "alias" => "jigawa",
                "lgas" => [
                    "Auyo",
                    "Babura",
                    "Buji",
                    "Biriniwa",
                    "Birnin Kudu",
                    "Dutse",
                    "Gagarawa",
                    "Garki",
                    "Gumel",
                    "Guri",
                    "Gwaram",
                    "Gwiwa",
                    "Hadejia",
                    "Jahun",
                    "Kafin Hausa",
                    "Kazaure",
                    "Kiri Kasama",
                    "Kiyawa",
                    "Kaugama",
                    "Maigatari",
                    "Malam Madori",
                    "Miga",
                    "Sule Tankarkar",
                    "Roni",
                    "Ringim",
                    "Yankwashi",
                    "Taura"
                ]
            ],
            [
                "state" => "Oyo",
                "alias" => "oyo",
                "lgas" => [
                    "Afijio",
                    "Akinyele",
                    "Atiba",
                    "Atisbo",
                    "Egbeda",
                    "Ibadan North",
                    "Ibadan North-East",
                    "Ibadan North-West",
                    "Ibadan South-East",
                    "Ibarapa Central",
                    "Ibadan South-West",
                    "Ibarapa East",
                    "Ido",
                    "Ibarapa North",
                    "Irepo",
                    "Iseyin",
                    "Itesiwaju",
                    "Iwajowa",
                    "Kajola",
                    "Lagelu",
                    "Ogbomosho North",
                    "Ogbomosho South",
                    "Ogo Oluwa",
                    "Olorunsogo",
                    "Oluyole",
                    "Ona Ara",
                    "Orelope",
                    "Ori Ire",
                    "Oyo",
                    "Oyo East",
                    "Saki East",
                    "Saki West",
                    "Surulere Oyo State"
                ]
            ],
            [
                "state" => "Imo",
                "alias" => "imo",
                "lgas" => [
                    "Aboh Mbaise",
                    "Ahiazu Mbaise",
                    "Ehime Mbano",
                    "Ezinihitte",
                    "Ideato North",
                    "Ideato South",
                    "Ihitte/Uboma",
                    "Ikeduru",
                    "Isiala Mbano",
                    "Mbaitoli",
                    "Isu",
                    "Ngor Okpala",
                    "Njaba",
                    "Nkwerre",
                    "Nwangele",
                    "Obowo",
                    "Oguta",
                    "Ohaji/Egbema",
                    "Okigwe",
                    "Orlu",
                    "Orsu",
                    "Oru East",
                    "Oru West",
                    "Owerri Municipal",
                    "Owerri North",
                    "Unuimo",
                    "Owerri West"
                ]
            ],
            [
                "state" => "Kaduna",
                "alias" => "kaduna",
                "lgas" => [
                    "Birnin Gwari",
                    "Chikun",
                    "Giwa",
                    "Ikara",
                    "Igabi",
                    "Jaba",
                    "Jema'a",
                    "Kachia",
                    "Kaduna North",
                    "Kaduna South",
                    "Kagarko",
                    "Kajuru",
                    "Kaura",
                    "Kauru",
                    "Kubau",
                    "Kudan",
                    "Lere",
                    "Makarfi",
                    "Sabon Gari",
                    "Sanga",
                    "Soba",
                    "Zangon Kataf",
                    "Zaria"
                ]
            ],
            [
                "state" => "Kebbi",
                "alias" => "kebbi",
                "lgas" => [
                    "Aleiro",
                    "Argungu",
                    "Arewa Dandi",
                    "Augie",
                    "Bagudo",
                    "Birnin Kebbi",
                    "Bunza",
                    "Dandi",
                    "Fakai",
                    "Gwandu",
                    "Jega",
                    "Kalgo",
                    "Koko/Besse",
                    "Maiyama",
                    "Ngaski",
                    "Shanga",
                    "Suru",
                    "Sakaba",
                    "Wasagu/Danko",
                    "Yauri",
                    "Zuru"
                ]
            ],
            [
                "state" => "Kano",
                "alias" => "kano",
                "lgas" => [
                    "Ajingi",
                    "Albasu",
                    "Bagwai",
                    "Bebeji",
                    "Bichi",
                    "Bunkure",
                    "Dala",
                    "Dambatta",
                    "Dawakin Kudu",
                    "Dawakin Tofa",
                    "Doguwa",
                    "Fagge",
                    "Gabasawa",
                    "Garko",
                    "Garun Mallam",
                    "Gezawa",
                    "Gaya",
                    "Gwale",
                    "Gwarzo",
                    "Kabo",
                    "Kano Municipal",
                    "Karaye",
                    "Kibiya",
                    "Kiru",
                    "Kumbotso",
                    "Kunchi",
                    "Kura",
                    "Madobi",
                    "Makoda",
                    "Minjibir",
                    "Nasarawa",
                    "Rano",
                    "Rimin Gado",
                    "Rogo",
                    "Shanono",
                    "Takai",
                    "Sumaila",
                    "Tarauni",
                    "Tofa",
                    "Tsanyawa",
                    "Tudun Wada",
                    "Ungogo",
                    "Warawa",
                    "Wudil"
                ]
            ],
            [
                "state" => "Kogi",
                "alias" => "kogi",
                "lgas" => [
                    "Ajaokuta",
                    "Adavi",
                    "Ankpa",
                    "Bassa",
                    "Dekina",
                    "Ibaji",
                    "Idah",
                    "Igalamela Odolu",
                    "Ijumu",
                    "Kogi",
                    "Kabba/Bunu",
                    "Lokoja",
                    "Ofu",
                    "Mopa Muro",
                    "Ogori/Magongo",
                    "Okehi",
                    "Okene",
                    "Olamaboro",
                    "Omala",
                    "Yagba East",
                    "Yagba West"
                ]
            ],
            [
                "state" => "Osun",
                "alias" => "osun",
                "lgas" => [
                    "Aiyedire",
                    "Atakunmosa West",
                    "Atakunmosa East",
                    "Aiyedaade",
                    "Boluwaduro",
                    "Boripe",
                    "Ife East",
                    "Ede South",
                    "Ife North",
                    "Ede North",
                    "Ife South",
                    "Ejigbo",
                    "Ife Central",
                    "Ifedayo",
                    "Egbedore",
                    "Ila",
                    "Ifelodun",
                    "Ilesa East",
                    "Ilesa West",
                    "Irepodun",
                    "Irewole",
                    "Isokan",
                    "Iwo",
                    "Obokun",
                    "Odo Otin",
                    "Ola Oluwa",
                    "Olorunda",
                    "Oriade",
                    "Orolu",
                    "Osogbo"
                ]
            ],
            [
                "state" => "Sokoto",
                "alias" => "sokoto",
                "lgas" => [
                    "Gudu",
                    "Gwadabawa",
                    "Illela",
                    "Isa",
                    "Kebbe",
                    "Kware",
                    "Rabah",
                    "Sabon Birni",
                    "Shagari",
                    "Silame",
                    "Sokoto North",
                    "Sokoto South",
                    "Tambuwal",
                    "Tangaza",
                    "Tureta",
                    "Wamako",
                    "Wurno",
                    "Yabo",
                    "Binji",
                    "Bodinga",
                    "Dange Shuni",
                    "Goronyo",
                    "Gada"
                ]
            ],
            [
                "state" => "Plateau",
                "alias" => "plateau",
                "lgas" => [
                    "Bokkos",
                    "Barkin Ladi",
                    "Bassa",
                    "Jos East",
                    "Jos North",
                    "Jos South",
                    "Kanam",
                    "Kanke",
                    "Langtang South",
                    "Langtang North",
                    "Mangu",
                    "Mikang",
                    "Pankshin",
                    "Qua'an Pan",
                    "Riyom",
                    "Shendam",
                    "Wase"
                ]
            ],
            [
                "state" => "Taraba",
                "alias" => "taraba",
                "lgas" => [
                    "Ardo Kola",
                    "Bali",
                    "Donga",
                    "Gashaka",
                    "Gassol",
                    "Ibi",
                    "Jalingo",
                    "Karim Lamido",
                    "Kumi",
                    "Lau",
                    "Sardauna",
                    "Takum",
                    "Ussa",
                    "Wukari",
                    "Yorro",
                    "Zing"
                ]
            ],
            [
                "state" => "Yobe",
                "alias" => "yobe",
                "lgas" => [
                    "Bade",
                    "Bursari",
                    "Damaturu",
                    "Fika",
                    "Fune",
                    "Geidam",
                    "Gujba",
                    "Gulani",
                    "Jakusko",
                    "Karasuwa",
                    "Machina",
                    "Nangere",
                    "Nguru",
                    "Potiskum",
                    "Tarmuwa",
                    "Yunusari",
                    "Yusufari"
                ]
            ],
            [
                "state" => "Zamfara",
                "alias" => "zamfara",
                "lgas" => [
                    "Anka",
                    "Birnin Magaji/Kiyaw",
                    "Bakura",
                    "Bukkuyum",
                    "Bungudu",
                    "Gummi",
                    "Gusau",
                    "Kaura Namoda",
                    "Maradun",
                    "Shinkafi",
                    "Maru",
                    "Talata Mafara",
                    "Tsafe",
                    "Zurmi"
                ]
            ],
            [
                "state" => "Lagos",
                "alias" => "lagos",
                "lgas" => [
                    "Agege",
                    "Ajeromi-Ifelodun",
                    "Alimosho",
                    "Amuwo-Odofin",
                    "Badagry",
                    "Apapa",
                    "Epe",
                    "Eti Osa",
                    "Ibeju-Lekki",
                    "Ifako-Ijaiye",
                    "Ikeja",
                    "Ikorodu",
                    "Kosofe",
                    "Lagos Island",
                    "Mushin",
                    "Lagos Mainland",
                    "Ojo",
                    "Oshodi-Isolo",
                    "Shomolu",
                    "Surulere Lagos State"
                ]
            ],
            [
                "state" => "Katsina",
                "alias" => "katsina",
                "lgas" => [
                    "Bakori",
                    "Batagarawa",
                    "Batsari",
                    "Baure",
                    "Bindawa",
                    "Charanchi",
                    "Danja",
                    "Dandume",
                    "Dan Musa",
                    "Daura",
                    "Dutsi",
                    "Dutsin Ma",
                    "Faskari",
                    "Funtua",
                    "Ingawa",
                    "Jibia",
                    "Kafur",
                    "Kaita",
                    "Kankara",
                    "Kankia",
                    "Katsina",
                    "Kurfi",
                    "Kusada",
                    "Mai'Adua",
                    "Malumfashi",
                    "Mani",
                    "Mashi",
                    "Matazu",
                    "Musawa",
                    "Rimi",
                    "Sabuwa",
                    "Safana",
                    "Sandamu",
                    "Zango"
                ]
            ],
            [
                "state" => "Kwara",
                "alias" => "kwara",
                "lgas" => [
                    "Asa",
                    "Baruten",
                    "Edu",
                    "Ilorin East",
                    "Ifelodun",
                    "Ilorin South",
                    "Ekiti Kwara State",
                    "Ilorin West",
                    "Irepodun",
                    "Isin",
                    "Kaiama",
                    "Moro",
                    "Offa",
                    "Oke Ero",
                    "Oyun",
                    "Pategi"
                ]
            ],
            [
                "state" => "Nasarawa",
                "alias" => "nasarawa",
                "lgas" => [
                    "Akwanga",
                    "Awe",
                    "Doma",
                    "Karu",
                    "Keana",
                    "Keffi",
                    "Lafia",
                    "Kokona",
                    "Nasarawa Egon",
                    "Nasarawa",
                    "Obi",
                    "Toto",
                    "Wamba"
                ]
            ],
            [
                "state" => "Niger",
                "alias" => "niger",
                "lgas" => [
                    "Agaie",
                    "Agwara",
                    "Bida",
                    "Borgu",
                    "Bosso",
                    "Chanchaga",
                    "Edati",
                    "Gbako",
                    "Gurara",
                    "Katcha",
                    "Kontagora",
                    "Lapai",
                    "Lavun",
                    "Mariga",
                    "Magama",
                    "Mokwa",
                    "Mashegu",
                    "Moya",
                    "Paikoro",
                    "Rafi",
                    "Rijau",
                    "Shiroro",
                    "Suleja",
                    "Tafa",
                    "Wushishi"
                ]
            ],
            [
                "state" => "Abia",
                "alias" => "abia",
                "lgas" => [
                    "Aba North",
                    "Arochukwu",
                    "Aba South",
                    "Bende",
                    "Isiala Ngwa North",
                    "Ikwuano",
                    "Isiala Ngwa South",
                    "Isuikwuato",
                    "Obi Ngwa",
                    "Ohafia",
                    "Osisioma",
                    "Ugwunagbo",
                    "Ukwa East",
                    "Ukwa West",
                    "Umuahia North",
                    "Umuahia South",
                    "Umu Nneochi"
                ]
            ]
        ];

        $lgas = null;
        if (!empty ($state)) {
            foreach ($states as $key => $value) {
                if (strtolower($state) == strtolower($value['state'])) {
                    $lgas = array_values($value['lgas']);
                }
            }
        }

        return $lgas;
    }
}

if (!function_exists("kycStatus")) {
    function kycStatus($key, $customer_id)
    {
        $data = KycData::where(['customer_id' => $customer_id, 'key' => $key])->first();

        if (!$data) {
            $data = collect([
                'key' => '',
                'value' => '',
                'status' => 'unverified'
            ]);
        }

        return $data;
    }
}

if (!function_exists("getFinalKycStatus")) {
    function getFinalKycStatus($customer_id)
    {
        return Customer::where(['id' => $customer_id])->value('kyc_status');
    }
}

if (!function_exists("starMiddle")) {
    function starMiddle($word, $a = 2, $b = 9, $c = 9, $d = 10)
    {
        return substr_replace($word, "*******", $a, $b) . substr($word, $c, $d);
    }
}

if (!function_exists("announcements")) {
    function announcements($type)
    {
        $ann = $ann = Announcement::all();

        if (count($ann)) {
            if ($type == 'scroll') {
                return $ann[1];
            } else {
                return $ann[0];
            }
        }
    }

    if (!function_exists("hasAccess")) {
        function hasAccess($route)
        {
            $routes = auth()->user()->admin->rolepermissions();
            
            if (in_array($route, $routes)|| in_array(1, auth()->user()->admin->roleIds())) {
                return true;
            }else{
                return false;
            }
        }
    }
}
