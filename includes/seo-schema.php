<?php
/**
 * Appsgain Technologies — Complete SEO Schema.org System
 * Generates JSON-LD structured data for all page types
 * Knowledge Graph · E-E-A-T · Rich Snippets · Local SEO
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

/* ── Business Core Data — 100% admin-controlled (settings table) ──
   Every value below is editable from Admin → Settings. The literals are
   only last-resort fallbacks for a brand-new/empty database. */
$_SEO_S = getAllSettings();

/** Read a setting with a fallback, trimming blanks. */
$_seoVal = static function (string $key, string $default = '') use ($_SEO_S): string {
    $v = trim((string)($_SEO_S[$key] ?? ''));
    return $v !== '' ? $v : $default;
};
/** Split a comma-separated admin field into a clean list. */
$_seoList = static function (string $key, array $default = []) use ($_seoVal): array {
    $raw = $_seoVal($key, '');
    if ($raw === '') return $default;
    return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
};

$_seoLogoRaw = $_seoVal('site_logo', '');
$_seoLogoUrl = $_seoLogoRaw === ''
    ? rtrim(SITE_URL, '/') . '/uploads/logo/appsgain-logo.png'
    : (str_starts_with($_seoLogoRaw, 'http') ? $_seoLogoRaw : rtrim(UPLOADS_URL, '/') . '/' . ltrim($_seoLogoRaw, '/'));

$_SEO_BIZ = [
  'name'         => $_seoVal('site_name',    'Appsgain Technologies'),
  'legalName'    => $_seoVal('company_name', 'Appsgain Technologies Private Limited'),
  'url'          => rtrim(SITE_URL, '/'),
  'logo'         => $_seoLogoUrl,
  'phone'        => $_seoVal('site_phone', '+91-9955446477'),
  'email'        => $_seoVal('site_email', 'info@appsgain.in'),
  'salesEmail'   => $_seoVal('sales_email', $_seoVal('site_email', 'info@appsgain.in')),
  'foundingYear' => $_seoVal('company_founded_year', '2018'),
  /* Blank unless Admin gives a figure — the old '11-50' default was a guess,
     and it was emitted as QuantitativeValue.value, which takes a number. */
  'employees'    => $_seoVal('company_employees', ''),
  'description'  => $_seoVal('site_description', $_seoVal('meta_description',
                     'Appsgain Technologies Private Limited is a software development company delivering custom web applications, mobile apps, ERP, CRM, AI solutions, SaaS platforms and enterprise software.')),
  'address' => [
    'street'   => $_seoVal('address_street',   'Wave One, 36th Floor, L-2A, Pocket G, Sector 18'),
    'locality' => $_seoVal('address_locality', 'Noida'),
    'region'   => $_seoVal('address_region',   'Uttar Pradesh'),
    'postal'   => $_seoVal('address_postal',   '201301'),
    'country'  => $_seoVal('address_country',  'IN'),
  ],
  'regionCode' => $_seoVal('address_region_code', 'IN-UP'),
  'geo' => [
    'lat' => (float)$_seoVal('geo_latitude',  '28.5706'),
    'lng' => (float)$_seoVal('geo_longitude', '77.3261'),
  ],
  'socials' => array_values(array_filter([
    $_seoVal('site_facebook'),
    $_seoVal('site_instagram'),
    $_seoVal('site_linkedin'),
    $_seoVal('site_twitter'),
    $_seoVal('site_youtube'),
    $_seoVal('site_threads'),
    $_seoVal('site_gmb'),
    $_seoVal('site_github'),
    $_seoVal('site_pinterest'),
  ], static fn($u) => $u !== '' && $u !== '#')),
  'serviceAreas'  => $_seoList('company_service_areas', ['India','United States','United Kingdom','United Arab Emirates','Australia','Canada']),
  'knowsAbout'    => $_seoList('company_knows_about', ['Software Development','Mobile App Development','Web Development','Cloud Computing','ERP Systems','CRM Development','SaaS Platforms']),
  'awards'        => $_seoList('company_awards', []),
  /* No '$$$' default. An unset price range is unknown, not cheap-or-dear,
     and inventing one puts a claim in front of Google we cannot stand by. */
  'priceRange'    => $_seoVal('company_price_range', ''),
  'slogan'        => $_seoVal('site_tagline', 'Transforming Ideas Into Digital Reality'),
  /* Founder — a named person tied to the company is one of the stronger
     entity signals Google has for a Knowledge Panel. */
  'founder'       => $_seoVal('company_founder', $_seoVal('founder_name', '')),
  /* Legal/birth name. Google treats alternateName as the same entity, which
     is how a search for either name resolves to one person. */
  'founderAltName'=> $_seoVal('founder_alt_name', 'Prashant Singh Kushwaha'),
  /* The founder's own profiles. Several independent, verifiable presences do
     far more for entity resolution than one, so the personal site sits here
     alongside LinkedIn and X. */
  'founderSocials'=> array_values(array_filter([
    $_seoVal('founder_linkedin', 'https://www.linkedin.com/in/prashantdevtech/'),
    $_seoVal('founder_twitter',  'https://x.com/PrashantDevtech'),
    $_seoVal('founder_website',  'https://prashantkushwaha.tech'),
  ], static fn($u) => $u !== '' && $u !== '#')),
  'founderRole'   => $_seoVal('founder_role', ''),
  /* Canonical home of the Person entity — both schemas point @id here so
     Organization and Person resolve to one linked pair, not two strangers. */
  'founderPage'   => rtrim(SITE_URL, '/') . '/our-founder.php',
  'cin'           => $_seoVal('company_cin', ''),
  'gstin'         => $_seoVal('company_gstin', ''),
  'vatId'         => $_seoVal('company_vat_id', ''),
  /* Blank unless an admin supplies figures from a real review platform.
     metric_clients used to feed reviewCount — a client count, not a
     review count, so the number was wrong as well as unverifiable. */
  'rating'        => $_seoVal('review_rating_value', ''),
  'reviewCount'   => $_seoVal('review_rating_count', ''),
  'hours'         => 'Mo-Sa 09:00-19:00',
  'naics'         => '541511',
];
$_SEO_BIZ['hasMap'] = 'https://maps.google.com/?q=' . rawurlencode(implode(', ', array_filter([
    $_SEO_BIZ['address']['street'],
    $_SEO_BIZ['address']['locality'],
    $_SEO_BIZ['address']['region'],
    $_SEO_BIZ['address']['postal'],
])));

/* ══════════════════════════════════════════════════════════
   1. ORGANIZATION SCHEMA
══════════════════════════════════════════════════════════ */
/** Real pixel size of the logo, so the declared dimensions match the file.
 *  Only measures a file we host; a remote logo URL is left undeclared
 *  rather than guessed. Read once per request. */
function seoLogoDimensions(string $logoUrl): array {
  static $cache = [];
  if (isset($cache[$logoUrl])) return $cache[$logoUrl];
  $dims = [];
  if (!defined('UPLOADS_URL') || !defined('UPLOADS_PATH')) return $cache[$logoUrl] = $dims;
  $base = rtrim(UPLOADS_URL, '/');
  if (str_starts_with($logoUrl, $base)) {
    $path = UPLOADS_PATH . '/' . ltrim(substr($logoUrl, strlen($base)), '/');
    if (is_file($path) && ($s = @getimagesize($path))) {
      $dims = ['width' => $s[0], 'height' => $s[1]];
    }
  }
  return $cache[$logoUrl] = $dims;
}

function seoOrganizationSchema(): array {
  global $_SEO_BIZ;

  /* Google reads Organization.founder as an entity link, so give it a real
     Person node rather than a bare string — and carry the same @id the
     founder page publishes, so the two describe one person, not two. */
  $founder = [];
  if ($_SEO_BIZ['founder'] !== '') {
    $founder = [
      '@type' => 'Person',
      '@id'   => $_SEO_BIZ['founderPage'] . '#person',
      'name'  => $_SEO_BIZ['founder'],
      'url'   => $_SEO_BIZ['founderPage'],
    ];
    if ($_SEO_BIZ['founderAltName'] !== '') $founder['alternateName'] = $_SEO_BIZ['founderAltName'];
    if ($_SEO_BIZ['founderRole']    !== '') $founder['jobTitle']      = $_SEO_BIZ['founderRole'];
    if ($_SEO_BIZ['founderSocials'] !== []) $founder['sameAs']        = $_SEO_BIZ['founderSocials'];
  }

  /* Company registration numbers identify the legal entity. Emitted only
     when Admin holds the real value — never invented. */
  $identifiers = [];
  foreach ([['CIN', $_SEO_BIZ['cin']], ['GSTIN', $_SEO_BIZ['gstin']], ['VAT', $_SEO_BIZ['vatId']]] as [$scheme, $val]) {
    if ($val !== '') {
      $identifiers[] = ['@type'=>'PropertyValue','propertyID'=>$scheme,'value'=>$val];
    }
  }

  /* "11-50" is a range, not a count, so it belongs in min/max — putting it
     in QuantitativeValue.value made the number unreadable to Google. */
  $employees = [];
  $emp = $_SEO_BIZ['employees'];
  if ($emp !== '') {
    if (preg_match('/^\s*(\d+)\s*[-–]\s*(\d+)\s*$/', $emp, $m)) {
      $employees = ['@type'=>'QuantitativeValue','minValue'=>(int)$m[1],'maxValue'=>(int)$m[2]];
    } elseif (ctype_digit(trim($emp))) {
      $employees = ['@type'=>'QuantitativeValue','value'=>(int)trim($emp)];
    }
  }

  /* foundingDate must be a date; a bare "2018" is a year. */
  $founded = $_SEO_BIZ['foundingYear'];
  if (preg_match('/^\d{4}$/', $founded)) $founded .= '-01-01';

  return [
    '@context'   => 'https://schema.org',
    '@type'      => ['Organization','LocalBusiness','ProfessionalService'],
    '@id'        => $_SEO_BIZ['url'].'/#organization',
    'name'       => $_SEO_BIZ['name'],
    'legalName'  => $_SEO_BIZ['legalName'],
    'alternateName' => $_SEO_BIZ['name'] !== $_SEO_BIZ['legalName'] ? $_SEO_BIZ['legalName'] : '',
    'url'        => $_SEO_BIZ['url'],
    'logo'       => array_merge([
      '@type'            => 'ImageObject',
      'url'              => $_SEO_BIZ['logo'],
      'caption'          => $_SEO_BIZ['name'].' Logo',
    ], seoLogoDimensions($_SEO_BIZ['logo'])),
    'image'       => $_SEO_BIZ['logo'],
    'description' => $_SEO_BIZ['description'],
    'telephone'   => $_SEO_BIZ['phone'],
    'email'       => $_SEO_BIZ['email'],
    'foundingDate'=> $founded,
    'founder'     => $founder,
    'foundingLocation' => [
      '@type'   => 'Place',
      'address' => [
        '@type'           => 'PostalAddress',
        'addressLocality' => $_SEO_BIZ['address']['locality'],
        'addressCountry'  => $_SEO_BIZ['address']['country'],
      ],
    ],
    'identifier'  => $identifiers,
    'naics'       => $_SEO_BIZ['naics'],
    'numberOfEmployees' => $employees,
    'address' => [
      '@type'           => 'PostalAddress',
      'streetAddress'   => $_SEO_BIZ['address']['street'],
      'addressLocality' => $_SEO_BIZ['address']['locality'],
      'addressRegion'   => $_SEO_BIZ['address']['region'],
      'postalCode'      => $_SEO_BIZ['address']['postal'],
      'addressCountry'  => $_SEO_BIZ['address']['country'],
    ],
    'geo' => [
      '@type'     => 'GeoCoordinates',
      'latitude'  => $_SEO_BIZ['geo']['lat'],
      'longitude' => $_SEO_BIZ['geo']['lng'],
    ],
    'contactPoint' => [
      [
        '@type'             => 'ContactPoint',
        'telephone'         => $_SEO_BIZ['phone'],
        'contactType'       => 'customer service',
        'email'             => $_SEO_BIZ['email'],
        'availableLanguage' => ['English','Hindi'],
        'areaServed'        => $_SEO_BIZ['serviceAreas'],
        'hoursAvailable'    => ['@type'=>'OpeningHoursSpecification','dayOfWeek'=>['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],'opens'=>'09:00','closes'=>'19:00'],
      ],
      [
        '@type'       => 'ContactPoint',
        'telephone'   => $_SEO_BIZ['phone'],
        'contactType' => 'sales',
        'email'       => $_SEO_BIZ['salesEmail'],
      ],
    ],
    'sameAs'    => $_SEO_BIZ['socials'],
    'openingHoursSpecification' => [
      '@type'    => 'OpeningHoursSpecification',
      'dayOfWeek'=> ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
      'opens'    => '09:00',
      'closes'   => '19:00',
    ],
    'areaServed'     => array_map(fn($s)=>['@type'=>'Country','name'=>$s], $_SEO_BIZ['serviceAreas']),
    /* No GeoCircle. A 5,000 km radius drawn from the Noida office was an
       invented figure that also contradicted areaServed above, which already
       names the countries served and is the accurate statement. */
    'priceRange'     => $_SEO_BIZ['priceRange'],
    'currenciesAccepted' => 'INR,USD,GBP,AED',
    'paymentAccepted'    => 'Cash,Credit Card,Bank Transfer,UPI',
    'hasMap'         => $_SEO_BIZ['hasMap'],
    'slogan'         => $_SEO_BIZ['slogan'],
    'knowsAbout'     => $_SEO_BIZ['knowsAbout'],
    'award'          => $_SEO_BIZ['awards'],
  ];
}

/* ══════════════════════════════════════════════════════════
   2. WEBSITE SCHEMA
══════════════════════════════════════════════════════════ */
function seoWebsiteSchema(): array {
  global $_SEO_BIZ;
  return [
    '@context'        => 'https://schema.org',
    '@type'           => 'WebSite',
    '@id'             => $_SEO_BIZ['url'].'/#website',
    'url'             => $_SEO_BIZ['url'],
    'name'            => $_SEO_BIZ['name'],
    'description'     => $_SEO_BIZ['description'],
    'publisher'       => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'inLanguage'      => 'en-IN',
    'copyrightYear'   => date('Y'),
    'copyrightHolder' => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    /* No SearchAction here. It used to point at /api/search.php, which
       returns JSON for the header search box — not a results page a person
       can read, which is what the schema is meant to advertise. Google
       crawled the literal "?q={search_term_string}" template because of it
       and logged the URL as crawled-not-indexed. Restore this only once a
       real HTML search results page exists to point it at. */
  ];
}

/* ══════════════════════════════════════════════════════════
   3. WEBPAGE SCHEMA
══════════════════════════════════════════════════════════ */
function seoWebPageSchema(string $title, string $desc, string $url, string $type = 'WebPage', ?string $lastMod = null, ?string $published = null): array {
  global $_SEO_BIZ;
  $schema = [
    '@context'          => 'https://schema.org',
    '@type'             => $type,
    '@id'               => $url.'#webpage',
    'url'               => $url,
    'name'              => $title,
    'description'       => $desc,
    'isPartOf'          => ['@id' => $_SEO_BIZ['url'].'/#website'],
    'about'             => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'inLanguage'        => 'en-IN',
    /* Dates we can stand behind, or none at all. datePublished was the
       literal '2024-01-01' on every page of the site, and dateModified was
       date('Y-m-d') — every page claiming it changed today, every day.
       Google discounts a date that always says now, and it discounts it
       site-wide, so the invented ones were costing the blog posts and
       services that genuinely had changed. The page file's mtime moves
       only when the page is actually edited; a caller with a real row
       date passes it in. */
    'datePublished'     => $published,
    'dateModified'      => $lastMod ?? pageLastModified(),
    'author'            => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'publisher'         => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'breadcrumb'        => ['@id' => $url.'#breadcrumb'],
    'potentialAction'   => ['@type'=>'ReadAction','target'=>[$url]],
  ];
  return $schema;
}

/* ══════════════════════════════════════════════════════════
   4. BREADCRUMB SCHEMA
══════════════════════════════════════════════════════════ */
function seoBreadcrumbSchema(array $items): array {
  global $_SEO_BIZ;
  $list = [];
  foreach ($items as $pos => [$name, $url]) {
    $item = ['@type'=>'ListItem','position'=>$pos+1,'name'=>$name];
    if ($url) $item['item'] = $url;
    $list[] = $item;
  }
  return [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => $list,
  ];
}

/* ══════════════════════════════════════════════════════════
   5. SERVICE SCHEMA
══════════════════════════════════════════════════════════ */
function seoServiceSchema(string $name, string $desc, string $url, string $category = 'Software Development', ?float $priceFrom = null): array {
  global $_SEO_BIZ;
  $schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => $url.'#service',
    'name'        => $name,
    'description' => $desc,
    'url'         => $url,
    'provider'    => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'serviceType' => $category,
    'category'    => $category,
    'areaServed'  => array_map(fn($s)=>['@type'=>'Country','name'=>$s], $_SEO_BIZ['serviceAreas']),
    'hasOfferCatalog' => ['@type'=>'OfferCatalog','name'=>$name.' Packages'],
    'availableChannel' => ['@type'=>'ServiceChannel','serviceUrl'=>$_SEO_BIZ['url'].'/contact.php'],
  ];
  if ($priceFrom) {
    $schema['offers'] = [
      '@type'         => 'Offer',
      'priceCurrency' => 'INR',
      'price'         => $priceFrom,
      'priceSpecification' => ['@type'=>'PriceSpecification','priceCurrency'=>'INR','price'=>$priceFrom],
      'seller'        => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    ];
  }
  return $schema;
}

/* ══════════════════════════════════════════════════════════
   6. FAQ SCHEMA
══════════════════════════════════════════════════════════ */
function seoFaqSchema(array $faqs): ?array {
  if (empty($faqs)) return null;
  $items = [];
  foreach ($faqs as $faq) {
    $q = $faq['question'] ?? '';
    $a = $faq['answer']   ?? '';
    if (!$q || !$a) continue;
    $items[] = [
      '@type'          => 'Question',
      'name'           => $q,
      'acceptedAnswer' => ['@type'=>'Answer','text'=>strip_tags($a)],
    ];
  }
  if (empty($items)) return null;
  return ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>$items];
}

/* ══════════════════════════════════════════════════════════
   7. BLOG POSTING SCHEMA
══════════════════════════════════════════════════════════ */
function seoBlogPostingSchema(array $post, string $url): array {
  global $_SEO_BIZ;
  $img   = !empty($post['featured_image']) ? UPLOADS_URL.'/'.$post['featured_image'] : $_SEO_BIZ['logo'];
  $dateP = date('c', strtotime($post['published_at'] ?? date('Y-m-d')));
  $dateM = date('c', strtotime($post['updated_at']   ?? date('Y-m-d')));
  return [
    '@context'         => 'https://schema.org',
    '@type'            => 'BlogPosting',
    '@id'              => $url.'#article',
    'headline'         => $post['title'] ?? '',
    'description'      => $post['excerpt'] ?? substr(strip_tags($post['content']??''),0,160),
    'image'            => ['@type'=>'ImageObject','url'=>$img,'width'=>1200,'height'=>630],
    'url'              => $url,
    'datePublished'    => $dateP,
    'dateModified'     => $dateM,
    'author'           => ['@type'=>'Person','name'=>$post['author_name']??$_SEO_BIZ['name'],'url'=>$_SEO_BIZ['url']],
    'publisher'        => [
      '@type'          => 'Organization',
      '@id'            => $_SEO_BIZ['url'].'/#organization',
      'name'           => $_SEO_BIZ['name'],
      'logo'           => ['@type'=>'ImageObject','url'=>$_SEO_BIZ['logo']],
    ],
    'mainEntityOfPage' => ['@type'=>'WebPage','@id'=>$url],
    'inLanguage'       => 'en-IN',
    'isPartOf'         => ['@id'=>$_SEO_BIZ['url'].'/#website'],
    'wordCount'        => str_word_count(strip_tags($post['content']??'')),
    'articleSection'   => 'Technology',
    'keywords'         => $post['meta_keywords'] ?? implode(',', $_SEO_BIZ['knowsAbout']),
  ];
}

/* ══════════════════════════════════════════════════════════
   8. REVIEW / RATING SCHEMA
══════════════════════════════════════════════════════════ */
function seoReviewSchema(array $testimonials): ?array {
  global $_SEO_BIZ;
  if (empty($testimonials)) return null;
  $reviews = [];
  foreach (array_slice($testimonials, 0, 5) as $t) {
    $reviews[] = [
      '@type'       => 'Review',
      'reviewRating'=> ['@type'=>'Rating','ratingValue'=>$t['rating']??5,'bestRating'=>5,'worstRating'=>1],
      'author'      => ['@type'=>'Person','name'=>$t['client_name']??'Client'],
      'reviewBody'  => $t['content'] ?? '',
      'datePublished'=> date('Y-m-d', strtotime($t['created_at']??'now')),
    ];
  }
  if (empty($reviews)) return null;
  $avgRating = round(array_sum(array_column($reviews,'reviewRating.ratingValue'))/ count($reviews) * 1, 1);
  return [
    '@context'        => 'https://schema.org',
    '@type'           => 'Organization',
    '@id'             => $_SEO_BIZ['url'].'/#organization',
    'name'            => $_SEO_BIZ['name'],
    'aggregateRating' => [
      '@type'       => 'AggregateRating',
      /* the real mean of the testimonials, not a flattering constant */
      'ratingValue' => (string)$avgRating,
      'reviewCount' => count($testimonials),
      'bestRating'  => '5',
      'worstRating' => '1',
    ],
    'review'          => $reviews,
  ];
}

/* ══════════════════════════════════════════════════════════
   9. PRODUCT SCHEMA
══════════════════════════════════════════════════════════ */
function seoProductSchema(array $product, string $url): array {
  global $_SEO_BIZ;
  $img = !empty($product['banner_image']) ? UPLOADS_URL.'/'.$product['banner_image']
       : (!empty($product['logo']) ? UPLOADS_URL.'/'.$product['logo'] : $_SEO_BIZ['logo']);
  return [
    '@context'    => 'https://schema.org',
    '@type'       => 'SoftwareApplication',
    '@id'         => $url.'#product',
    'name'        => $product['name'] ?? '',
    'description' => $product['short_description'] ?? $product['description'] ?? '',
    'url'         => $url,
    'image'       => $img,
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem'     => 'Web, Android, iOS',
    'offers'      => [
      '@type'         => 'Offer',
      'priceCurrency' => 'INR',
      'price'         => '0',
      'priceSpecification' => ['@type'=>'PriceSpecification','description'=>'Custom pricing available'],
      'seller'        => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    ],
    'publisher'   => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'author'      => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    /* removed: 4.8 / 50 was invented */
  ];
}

/* ══════════════════════════════════════════════════════════
   10. JOB POSTING SCHEMA
══════════════════════════════════════════════════════════ */
function seoJobPostingSchema(array $job, string $url): array {
  global $_SEO_BIZ;
  return [
    '@context'          => 'https://schema.org',
    '@type'             => 'JobPosting',
    '@id'               => $url.'#job',
    'title'             => $job['title'] ?? '',
    'description'       => $job['description'] ?? '',
    'url'               => $url,
    'datePosted'        => date('Y-m-d', strtotime($job['created_at']??'now')),
    'validThrough'      => date('Y-m-d', strtotime('+90 days')),
    'employmentType'    => strtoupper(str_replace('-','_',$job['type']??'FULL_TIME')),
    'hiringOrganization' => [
      '@type'           => 'Organization',
      '@id'             => $_SEO_BIZ['url'].'/#organization',
      'name'            => $_SEO_BIZ['name'],
      'sameAs'          => $_SEO_BIZ['url'],
      'logo'            => $_SEO_BIZ['logo'],
    ],
    'jobLocation'       => [
      '@type'           => 'Place',
      'address'         => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => $_SEO_BIZ['address']['street'],
        'addressLocality' => $_SEO_BIZ['address']['locality'],
        'addressRegion'   => $_SEO_BIZ['address']['region'],
        'postalCode'      => $_SEO_BIZ['address']['postal'],
        'addressCountry'  => $_SEO_BIZ['address']['country'],
      ],
    ],
    'baseSalary'        => !empty($job['salary_range']) ? [
      '@type'           => 'MonetaryAmount',
      'currency'        => 'INR',
      'value'           => ['@type'=>'QuantitativeValue','unitText'=>'YEAR','description'=>$job['salary_range']],
    ] : null,
    'experienceRequirements' => $job['experience'] ?? '',
    'skills'            => $job['skills'] ?? '',
    'industry'          => 'Information Technology',
    'occupationalCategory' => '15-1252.00',
  ];
}

/* ══════════════════════════════════════════════════════════
   11. COURSE SCHEMA
══════════════════════════════════════════════════════════ */
function seoCourseSchema(array $course, string $url): array {
  global $_SEO_BIZ;
  return [
    '@context'    => 'https://schema.org',
    '@type'       => 'Course',
    '@id'         => $url.'#course',
    'name'        => $course['title'] ?? '',
    'description' => $course['short_description'] ?? $course['description'] ?? '',
    'url'         => $url,
    'provider'    => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'offers'      => [
      '@type'         => 'Offer',
      'priceCurrency' => 'INR',
      'price'         => $course['price'] ?? '0',
      'availability'  => 'https://schema.org/InStock',
      'validFrom'     => date('Y-m-d'),
    ],
    'courseMode'  => $course['mode'] ?? 'Online',
    'duration'    => 'P'.(preg_replace('/[^0-9]/','',$course['duration']??'8').'W'),
    'hasCourseInstance' => [
      '@type'        => 'CourseInstance',
      'courseMode'   => $course['mode'] ?? 'Online',
      'instructor'   => ['@type'=>'Person','name'=>'Expert Trainer','affiliation'=>['@id'=>$_SEO_BIZ['url'].'/#organization']],
    ],
    'educationalLevel'   => ucfirst($course['level']??'beginner'),
    'inLanguage'         => 'en-IN',
    'teaches'            => $course['title'] ?? '',
    /* removed: 4.8 / 120 was invented */
    'image'              => !empty($course['image']) ? UPLOADS_URL.'/'.$course['image'] : $_SEO_BIZ['logo'],
  ];
}

/* ══════════════════════════════════════════════════════════
   12. PERSON SCHEMA (Team Member)
══════════════════════════════════════════════════════════ */
function seoPersonSchema(array $member): array {
  global $_SEO_BIZ;
  return [
    '@context'     => 'https://schema.org',
    '@type'        => 'Person',
    'name'         => $member['name'] ?? '',
    'jobTitle'     => $member['designation'] ?? '',
    'description'  => $member['bio'] ?? '',
    'image'        => !empty($member['photo']) ? UPLOADS_URL.'/'.$member['photo'] : $_SEO_BIZ['logo'],
    'worksFor'     => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'email'        => $member['email'] ?? $_SEO_BIZ['email'],
    'url'          => $_SEO_BIZ['url'],
    'sameAs'       => array_filter([$member['linkedin']??null,$member['twitter']??null]),
    'knowsAbout'   => [$member['department']??'Technology'],
  ];
}

/* ══════════════════════════════════════════════════════════
   13. CONTACT PAGE SCHEMA
══════════════════════════════════════════════════════════ */
function seoContactPageSchema(string $url): array {
  global $_SEO_BIZ;
  return [
    '@context'  => 'https://schema.org',
    '@type'     => 'ContactPage',
    '@id'       => $url.'#contactpage',
    'name'      => 'Contact ' . $_SEO_BIZ['name'],
    'url'       => $url,
    'description'=> 'Get in touch with ' . $_SEO_BIZ['legalName'] . ' for software development projects, IT services, partnerships and career opportunities.',
    'mainEntity'=> ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'publisher' => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'about'     => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'inLanguage'=> 'en-IN',
  ];
}

/* ══════════════════════════════════════════════════════════
   14. ABOUT PAGE SCHEMA
══════════════════════════════════════════════════════════ */
function seoAboutPageSchema(string $url): array {
  global $_SEO_BIZ;
  return [
    '@context'   => 'https://schema.org',
    '@type'      => 'AboutPage',
    '@id'        => $url.'#aboutpage',
    'name'       => 'About ' . $_SEO_BIZ['name'],
    'url'        => $url,
    'description'=> 'Learn about ' . $_SEO_BIZ['legalName'] . ' — our story, mission, vision, core values, team and achievements.',
    'mainEntity' => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'publisher'  => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'about'      => ['@id' => $_SEO_BIZ['url'].'/#organization'],
    'inLanguage' => 'en-IN',
  ];
}

/* ══════════════════════════════════════════════════════════
   15. LOCAL BUSINESS SCHEMA (Standalone)
══════════════════════════════════════════════════════════ */
function seoLocalBusinessSchema(): array {
  global $_SEO_BIZ;
  return [
    '@context'  => 'https://schema.org',
    '@type'     => 'LocalBusiness',
    '@id'       => $_SEO_BIZ['url'].'/#localbusiness',
    'name'      => $_SEO_BIZ['name'],
    'image'     => $_SEO_BIZ['logo'],
    'url'       => $_SEO_BIZ['url'],
    'telephone' => $_SEO_BIZ['phone'],
    'email'     => $_SEO_BIZ['email'],
    'address'   => [
      '@type'           => 'PostalAddress',
      'streetAddress'   => $_SEO_BIZ['address']['street'],
      'addressLocality' => $_SEO_BIZ['address']['locality'],
      'addressRegion'   => $_SEO_BIZ['address']['region'],
      'postalCode'      => $_SEO_BIZ['address']['postal'],
      'addressCountry'  => $_SEO_BIZ['address']['country'],
    ],
    'geo'               => ['@type'=>'GeoCoordinates','latitude'=>$_SEO_BIZ['geo']['lat'],'longitude'=>$_SEO_BIZ['geo']['lng']],
    'openingHours'      => ['Mo-Sa 09:00-19:00'],
    'priceRange'        => $_SEO_BIZ['priceRange'],
    'currenciesAccepted'=> 'INR',
    'paymentAccepted'   => 'Cash,Credit Card,Bank Transfer',
    /* Omitted entirely unless both a rating and a count are configured. */
    'aggregateRating'   => ($_SEO_BIZ['rating'] !== '' && (int)$_SEO_BIZ['reviewCount'] > 0)
        ? ['@type'=>'AggregateRating','ratingValue'=>$_SEO_BIZ['rating'],'reviewCount'=>$_SEO_BIZ['reviewCount'],'bestRating'=>'5','worstRating'=>'1']
        : null,
    'sameAs'            => $_SEO_BIZ['socials'],
    'hasMap'            => $_SEO_BIZ['hasMap'],
    'knowsAbout'        => $_SEO_BIZ['knowsAbout'],
  ];
}

/* ══════════════════════════════════════════════════════════
   16. KNOWLEDGE GRAPH / SAMEÁS Dataset
══════════════════════════════════════════════════════════ */
function seoKnowledgeGraphDataset(): array {
  global $_SEO_BIZ;
  return [
    '@context' => 'https://schema.org',
    /* One company, one node. seoLocalBusinessSchema() used to sit here too,
       publishing a second #localbusiness entity with the same name, address,
       phone, geo and sameAs as #organization — which already carries the
       LocalBusiness type. Two competing nodes for one company is what makes
       Google hedge on which entity is canonical, so the duplicate is gone.
       The function is kept for pages that want a standalone node. */
    '@graph'   => [
      seoOrganizationSchema(),
      seoWebsiteSchema(),
    ],
  ];
}

/* ══════════════════════════════════════════════════════════
   MASTER RENDERER — output all applicable schemas for current page
══════════════════════════════════════════════════════════ */
/**
 * Recursively drop null / '' / [] entries so blank admin fields never
 * reach the JSON-LD output. Numeric keys are re-indexed.
 */
function seoPruneEmpty(array $data): array {
  $out = [];
  foreach ($data as $k => $v) {
    if (is_array($v)) {
      $v = seoPruneEmpty($v);
      if ($v === []) continue;
    } elseif ($v === null || $v === '' || (is_string($v) && trim($v) === '')) {
      continue;
    }
    $out[$k] = $v;
  }
  return array_is_list($data) ? array_values($out) : $out;
}

function renderPageSchema(string $pageType, array $pageData = []): void {
  global $_SEO_BIZ;
  $schemas = [];

  /* Every page gets website + organization */
  $schemas[] = seoKnowledgeGraphDataset();

  $url       = $pageData['url']   ?? (SITE_URL . ($_SERVER['REQUEST_URI'] ?? '/'));
  /* Real dates when the page has them — a service or product row's
     updated_at beats the template file's mtime, and only a page that
     knows when it was first published gets to say so. */
  $lastMod   = $pageData['lastmod']   ?? null;
  $published = $pageData['published'] ?? null;
  $title     = $pageData['title'] ?? $_SEO_BIZ['name'];
  $desc      = $pageData['desc']  ?? $_SEO_BIZ['description'];

  switch ($pageType) {
    case 'home':
      $schemas[] = seoWebPageSchema($title, $desc, $url, 'WebPage', $lastMod, $published);
      if (!empty($pageData['faqs']))      $schemas[] = seoFaqSchema($pageData['faqs']);
      if (!empty($pageData['testimonials'])) $schemas[] = seoReviewSchema($pageData['testimonials']);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'], SITE_URL]]);
      break;

    case 'about':
      $schemas[] = seoAboutPageSchema($url);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['About Us','']]);
      if (!empty($pageData['team'])) {
        foreach (array_slice($pageData['team'],0,5) as $m) $schemas[] = seoPersonSchema($m);
      }
      break;

    case 'services':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Services','']]);
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      break;

    case 'service':
      $svc = $pageData['service'] ?? [];
      $schemas[] = seoServiceSchema($svc['name']??$title, $svc['description']??$desc, $url, $svc['service_group']??'Software Development');
      /* The service row's own updated_at is the true date of this page's
         content; the template file's mtime only says when the layout moved. */
      $svcMod = $lastMod ?? (!empty($svc['updated_at']) ? date('Y-m-d', strtotime($svc['updated_at'])) : null);
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage', $svcMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Services',SITE_URL.'/services.php'],[$svc['name']??$title,'']]);
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      break;

    case 'blog':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Blog','']]);
      break;

    case 'blog_post':
      $post = $pageData['post'] ?? [];
      $schemas[] = seoBlogPostingSchema($post, $url);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Blog',SITE_URL.'/blog.php'],[$post['title']??'Article','']]);
      break;

    case 'contact':
      $schemas[] = seoContactPageSchema($url);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Contact Us','']]);
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      break;

    case 'faq':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'FAQPage', $lastMod, $published);
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['FAQs','']]);
      break;

    case 'portfolio':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Portfolio','']]);
      break;

    case 'careers':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage', $lastMod, $published);
      if (!empty($pageData['jobs'])) {
        foreach (array_slice($pageData['jobs'],0,10) as $j) {
          $schemas[] = seoJobPostingSchema($j, SITE_URL.'/careers.php#job-'.$j['id']);
        }
      }
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Careers','']]);
      break;

    case 'products':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Products','']]);
      break;

    case 'product':
      $product = $pageData['product'] ?? [];
      $schemas[] = seoProductSchema($product, $url);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Products',SITE_URL.'/products.php'],[$product['name']??$title,'']]);
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      break;

    case 'courses':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['IT Courses','']]);
      break;

    case 'course':
      $course = $pageData['course'] ?? [];
      $schemas[] = seoCourseSchema($course, $url);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Courses',SITE_URL.'/courses.php'],[$course['title']??$title,'']]);
      break;

    case 'partners':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Partners','']]);
      break;

    case 'clients':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Clients','']]);
      if (!empty($pageData['testimonials'])) $schemas[] = seoReviewSchema($pageData['testimonials']);
      break;

    case 'gallery':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'ImageGallery', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Gallery','']]);
      break;

    case 'testimonials':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage', $lastMod, $published);
      if (!empty($pageData['testimonials'])) $schemas[] = seoReviewSchema($pageData['testimonials']);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Testimonials','']]);
      break;

    case 'apps':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage', $lastMod, $published);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Mobile Apps','']]);
      break;

    /* Founder bio — a Person profile, which is what Google reads for
       knowledge-panel and author signals. */
    case 'person':
      $p = $pageData['person'] ?? [];
      $schemas[] = seoPruneEmpty([
        '@context' => 'https://schema.org',
        '@type'    => 'Person',
        /* Same @id the Organization's founder node uses, so Google reads one
           person described in two places rather than two similar people. */
        '@id'      => $_SEO_BIZ['founderPage'] . '#person',
        'name'     => $p['name']     ?? '',
        'alternateName' => $_SEO_BIZ['founderAltName'],
        'jobTitle' => $p['jobTitle'] ?? '',
        'description' => $p['description'] ?? $desc,
        'url'      => $url,
        'image'    => $p['image'] ?? '',
        'email'    => $p['email'] ?? '',
        /* Page-supplied profiles first, then the configured ones, deduped. */
        'sameAs'   => array_values(array_unique(array_filter(array_merge(
                        $p['sameAs'] ?? [], $_SEO_BIZ['founderSocials']
                      )))),
        'knowsAbout' => array_values(array_filter($p['knowsAbout'] ?? [])),
        'alumniOf' => !empty($p['alumniOf']) ? ['@type'=>'EducationalOrganization','name'=>$p['alumniOf']] : null,
        /* Reference, not a copy. This used to inline a second Organization
           with its own name and url, competing with #organization. */
        'worksFor'   => ['@id' => $_SEO_BIZ['url'].'/#organization'],
        'founderOf'  => ['@id' => $_SEO_BIZ['url'].'/#organization'],
      ]);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Our Founder','']]);
      break;

    default:
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage', $lastMod, $published);
      break;
  }

  /* ── Breadcrumb integrity ──────────────────────────────────────────
     Every WebPage node points at <url>#breadcrumb. Two things used to
     break that reference, and both made Google report the dangling target
     as an empty BreadcrumbList ("Missing field itemListElement"):

       1. seoBreadcrumbSchema() never set an @id, so nothing answered to
          the reference even when a real breadcrumb was emitted.
       2. The default: case above emits no breadcrumb at all, so every
          page outside $_typeMap — the legal pages, sitemap, 404 — left
          the reference pointing at nothing.

     Fixed here rather than in each case so a future page type cannot
     reintroduce it. */
  $_hasCrumb = false;
  foreach ($schemas as $_i => $_s) {
    if (is_array($_s) && ($_s['@type'] ?? '') === 'BreadcrumbList') {
      $_hasCrumb = true;
      if (empty($_s['@id'])) $schemas[$_i]['@id'] = $url . '#breadcrumb';
    }
  }
  if (!$_hasCrumb) {
    /* Build Home → This page. The title carries a " | Brand" suffix from
       meta.php, which reads badly as a crumb label, so trim it. */
    $_leaf = trim(preg_split('/\s+[|–—-]\s+/u', $title)[0] ?? $title);
    if ($_leaf === '') $_leaf = $_SEO_BIZ['name'];
    $_crumb = seoBreadcrumbSchema([[$_SEO_BIZ['name'], SITE_URL], [$_leaf, '']]);
    $_crumb['@id'] = $url . '#breadcrumb';
    $schemas[] = $_crumb;
  }

  /* Output all schemas.
     Values left blank in Admin are pruned so we never emit empty
     schema.org properties (Google flags those as invalid). */
  foreach (array_filter($schemas) as $schema) {
    $schema = seoPruneEmpty($schema);
    if (empty($schema)) continue;
    echo '<script type="application/ld+json">'."\n";
    echo json_encode($schema, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    echo "\n".'</script>'."\n";
  }
}
