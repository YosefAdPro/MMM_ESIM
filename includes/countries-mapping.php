<?php
function AdPro_get_countries_mapping() {
    // קבלת המדינות מהגדרות
    $countries = get_option('AdPro_countries_mapping', []);
    
    // אם אין מדינות מוגדרות, השתמש במערך ברירת מחדל
    if (empty($countries)) {
return [
    'ישראל' => [
        'english' => 'ISRAEL',
        'iso' => 'IL',
        'slug' => 'israel'
    ],
    'ארצות הברית' => [
        'english' => 'UNITED STATES',
        'iso' => 'US',
        'slug' => 'united-states'
    ],
    'צרפת' => [
        'english' => 'FRANCE',
        'iso' => 'FR',
        'slug' => 'france'
    ],
    'גרמניה' => [
        'english' => 'GERMANY',
        'iso' => 'DE',
        'slug' => 'germany'
    ],
    'יפן' => [
        'english' => 'JAPAN',
        'iso' => 'JP',
        'slug' => 'japan'
    ],
    'פולין' => [
        'english' => 'POLAND',
        'iso' => 'PL',
        'slug' => 'poland'
    ],
    'אוסטריה' => [
        'english' => 'AUSTRIA',
        'iso' => 'AT',
        'slug' => 'austria'
    ],
    'בלגיה' => [
        'english' => 'BELGIUM',
        'iso' => 'BE',
        'slug' => 'belgium'
    ],
    'בולגריה' => [
        'english' => 'BULGARIA',
        'iso' => 'BG',
        'slug' => 'bulgaria'
    ],
    'קרואטיה' => [
        'english' => 'CROATIA',
        'iso' => 'HR',
        'slug' => 'croatia'
    ],    
	'מולדובה' => [
        'english' => 'MOLDOVA',
        'iso' => 'MD',
        'slug' => 'moldova'
    ],
    'קפריסין' => [
        'english' => 'CYPRUS',
        'iso' => 'CY',
        'slug' => 'cyprus'
    ],
    'צ׳כיה' => [
        'english' => 'CZECH REPUBLIC',
        'iso' => 'CZ',
        'slug' => 'czech-republic'
    ],
    'דנמרק' => [
        'english' => 'DENMARK',
        'iso' => 'DK',
        'slug' => 'denmark'
    ],
    'אסטוניה' => [
        'english' => 'ESTONIA',
        'iso' => 'EE',
        'slug' => 'estonia'
    ],
    'פינלנד' => [
        'english' => 'FINLAND',
        'iso' => 'FI',
        'slug' => 'finland'
    ],
    'יוון' => [
        'english' => 'GREECE',
        'iso' => 'GR',
        'slug' => 'greece'
    ],
    'הונגריה' => [
        'english' => 'HUNGARY',
        'iso' => 'HU',
        'slug' => 'hungary'
    ],
    'איסלנד' => [
        'english' => 'ICELAND',
        'iso' => 'IS',
        'slug' => 'iceland'
    ],
    'אירלנד' => [
        'english' => 'IRELAND',
        'iso' => 'IE',
        'slug' => 'ireland'
    ],
    'איטליה' => [
        'english' => 'ITALY',
        'iso' => 'IT',
        'slug' => 'italy'
    ],
    'לטביה' => [
        'english' => 'LATVIA',
        'iso' => 'LV',
        'slug' => 'latvia'
    ],
    'ליכטנשטיין' => [
        'english' => 'LIECHTENSTEIN',
        'iso' => 'LI',
        'slug' => 'liechtenstein'
    ],
    'ליטא' => [
        'english' => 'LITHUANIA',
        'iso' => 'LT',
        'slug' => 'lithuania'
    ],
    'לוקסמבורג' => [
        'english' => 'LUXEMBOURG',
        'iso' => 'LU',
        'slug' => 'luxembourg'
    ],
    'מלטה' => [
        'english' => 'MALTA',
        'iso' => 'MT',
        'slug' => 'malta'
    ],
    'הולנד' => [
        'english' => 'NETHERLANDS',
        'iso' => 'NL',
        'slug' => 'netherlands'
    ],
    'פורטוגל' => [
        'english' => 'PORTUGAL',
        'iso' => 'PT',
        'slug' => 'portugal'
    ],
    'רומניה' => [
        'english' => 'ROMANIA',
        'iso' => 'RO',
        'slug' => 'romania'
    ],
    'סלובקיה' => [
        'english' => 'SLOVAKIA',
        'iso' => 'SK',
        'slug' => 'slovakia'
    ],
    'סלובניה' => [
        'english' => 'SLOVENIA',
        'iso' => 'SI',
        'slug' => 'slovenia'
    ],
    'ספרד' => [
        'english' => 'SPAIN',
        'iso' => 'ES',
        'slug' => 'spain'
    ],
    'שוודיה' => [
        'english' => 'SWEDEN',
        'iso' => 'SE',
        'slug' => 'sweden'
    ],
    'שווייץ' => [
        'english' => 'SWITZERLAND',
        'iso' => 'CH',
        'slug' => 'switzerland'
    ],
    'טורקיה' => [
        'english' => 'TURKEY',
        'iso' => 'TR',
        'slug' => 'turkey'
    ],
    'בריטניה' => [
        'english' => 'UNITED KINGDOM',
        'iso' => 'GB',
        'slug' => 'united-kingdom'
    ],
    'הוותיקן' => [
        'english' => 'VATICAN CITY',
        'iso' => 'VA',
        'slug' => 'vatican-city'
    ],

    'אלבניה' => [
        'english' => 'ALBANIA',
        'iso' => 'AL',
        'slug' => 'albania'
    ],
    'אלג׳יריה' => [
        'english' => 'ALGERIA',
        'iso' => 'DZ',
        'slug' => 'algeria'
    ],
    'אנדורה' => [
        'english' => 'ANDORRA',
        'iso' => 'AD',
        'slug' => 'andorra'
    ],
    'ארגנטינה' => [
        'english' => 'ARGENTINA',
        'iso' => 'AR',
        'slug' => 'argentina'
    ],
    'בחריין' => [
        'english' => 'BAHRAIN',
        'iso' => 'BH',
        'slug' => 'bahrain'
    ],
    'בנגלדש' => [
        'english' => 'BANGLADESH',
        'iso' => 'BD',
        'slug' => 'bangladesh'
    ],
    'בלארוס' => [
        'english' => 'BELARUS',
        'iso' => 'BY',
        'slug' => 'belarus'
    ],
    'בוסניה והרצגובינה' => [
        'english' => 'BOSNIA AND HERZEGOVINA',
        'iso' => 'BA',
        'slug' => 'bosnia-and-herzegovina'
    ],
    'ברזיל' => [
        'english' => 'BRAZIL',
        'iso' => 'BR',
        'slug' => 'brazil'
    ],
    'קמבודיה' => [
        'english' => 'CAMBODIA',
        'iso' => 'KH',
        'slug' => 'cambodia'
    ],
    'קנדה' => [
        'english' => 'CANADA',
        'iso' => 'CA',
        'slug' => 'canada'
    ],
    'צ׳אד' => [
        'english' => 'CHAD',
        'iso' => 'TD',
        'slug' => 'chad'
    ],
    'צ׳ילה' => [
        'english' => 'CHILE',
        'iso' => 'CL',
        'slug' => 'chile'
    ],
    'סין' => [
        'english' => 'CHINA',
        'iso' => 'CN',
        'slug' => 'china'
    ],
    'קוסטה ריקה' => [
        'english' => 'COSTA RICA',
        'iso' => 'CR',
        'slug' => 'costa-rica'
    ],
    'אקוודור' => [
        'english' => 'ECUADOR',
        'iso' => 'EC',
        'slug' => 'ecuador'
    ],
    'מצרים' => [
        'english' => 'EGYPT',
        'iso' => 'EG',
        'slug' => 'egypt'
    ],
    'גאורגיה' => [
        'english' => 'GEORGIA',
        'iso' => 'GE',
        'slug' => 'georgia'
    ],
    'גאנה' => [
        'english' => 'GHANA',
        'iso' => 'GH',
        'slug' => 'ghana'
    ],
    'גיברלטר' => [
        'english' => 'GIBRALTAR',
        'iso' => 'GI',
        'slug' => 'gibraltar'
    ],
    'הודו' => [
        'english' => 'INDIA',
        'iso' => 'IN',
        'slug' => 'india'
    ],
    'אינדונזיה' => [
        'english' => 'INDONESIA',
        'iso' => 'ID',
        'slug' => 'indonesia'
    ],
    'יפן' => [
        'english' => 'JAPAN',
        'iso' => 'JP',
        'slug' => 'japan'
    ],
    'ירדן' => [
        'english' => 'JORDAN',
        'iso' => 'JO',
        'slug' => 'jordan'
    ],
    'קניה' => [
        'english' => 'KENYA',
        'iso' => 'KE',
        'slug' => 'kenya'
    ],
    'כווית' => [
        'english' => 'KUWAIT',
        'iso' => 'KW',
        'slug' => 'kuwait'
    ],
    'מקאו' => [
        'english' => 'MACAO',
        'iso' => 'MO',
        'slug' => 'macao'
    ],
    'מדגסקר' => [
        'english' => 'MADAGASCAR',
        'iso' => 'MG',
        'slug' => 'madagascar'
    ],
    'מלאווי' => [
        'english' => 'MALAWI',
        'iso' => 'MW',
        'slug' => 'malawi'
    ],
    'מלזיה' => [
        'english' => 'MALAYSIA',
        'iso' => 'MY',
        'slug' => 'malaysia'
    ],
    'מקסיקו' => [
        'english' => 'MEXICO',
        'iso' => 'MX',
        'slug' => 'mexico'
    ],
    'מונגוליה' => [
        'english' => 'MONGOLIA',
        'iso' => 'MN',
        'slug' => 'mongolia'
    ],
    'מרוקו' => [
        'english' => 'MOROCCO',
        'iso' => 'MA',
        'slug' => 'morocco'
    ],
    'נפאל' => [
        'english' => 'NEPAL',
        'iso' => 'NP',
        'slug' => 'nepal'
    ],
    'ניז׳ר' => [
        'english' => 'NIGER',
        'iso' => 'NE',
        'slug' => 'niger'
    ],
    'ניגריה' => [
        'english' => 'NIGERIA',
        'iso' => 'NG',
        'slug' => 'nigeria'
    ],
    'עומאן' => [
        'english' => 'OMAN',
        'iso' => 'OM',
        'slug' => 'oman'
    ],
    'פרגוואי' => [
        'english' => 'PARAGUAY',
        'iso' => 'PY',
        'slug' => 'paraguay'
    ],
    'פרו' => [
        'english' => 'PERU',
        'iso' => 'PE',
        'slug' => 'peru'
    ],
    'קטאר' => [
        'english' => 'QATAR',
        'iso' => 'QA',
        'slug' => 'qatar'
    ],
    'סן מרינו' => [
        'english' => 'SAN MARINO',
        'iso' => 'SM',
        'slug' => 'san-marino'
    ],
    'ערב הסעודית' => [
        'english' => 'SAUDI ARABIA',
        'iso' => 'SA',
        'slug' => 'saudi-arabia'
    ],
    'דרום אפריקה' => [
        'english' => 'SOUTH AFRICA',
        'iso' => 'ZA',
        'slug' => 'south-africa'
    ],
    'טנזניה' => [
        'english' => 'TANZANIA',
        'iso' => 'TZ',
        'slug' => 'tanzania'
    ],
    'טוניסיה' => [
        'english' => 'TUNISIA',
        'iso' => 'TN',
        'slug' => 'tunisia'
    ],
    'אוגנדה' => [
        'english' => 'UGANDA',
        'iso' => 'UG',
        'slug' => 'uganda'
    ],
    'איחוד האמירויות' => [
        'english' => 'UNITED ARAB EMIRATES',
        'iso' => 'AE',
        'slug' => 'united-arab-emirates'
    ],
    'אורוגוואי' => [
        'english' => 'URUGUAY',
        'iso' => 'UY',
        'slug' => 'uruguay'
    ],
	
    'אוקראינה' => [
        'english' => 'UKRAINE',
        'iso' => 'UA',
        'slug' => 'ukraine'
    ],
    'כווית' => [
        'english' => 'KUWAIT',
        'iso' => 'KW',
        'slug' => 'kuwait'
    ],
    'פרו' => [
        'english' => 'PERU',
        'iso' => 'PE',
        'slug' => 'peru'
    ],
    'אקוודור' => [
        'english' => 'ECUADOR',
        'iso' => 'EC',
        'slug' => 'ecuador'
    ],
    'ונצואלה' => [
        'english' => 'VENEZUELA',
        'iso' => 'VE',
        'slug' => 'venezuela'
    ],
    'קניה' => [
        'english' => 'KENYA',
        'iso' => 'KE',
        'slug' => 'kenya'
    ]

];


   
    }
    
    return $countries;
}

// פונקציה לקבלת מדינה לפי slug
function AdPro_get_country_by_slug($slug) {
    $countries = AdPro_get_countries_mapping();
    foreach ($countries as $hebrew => $data) {
        if ($data['slug'] === $slug) {
            return [
                'hebrew' => $hebrew,
                'english' => $data['english'],
                'iso' => $data['iso'],
                'slug' => $data['slug']
            ];
        }
    }
    return null;
}