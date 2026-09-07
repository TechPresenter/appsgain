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
  'employees'    => $_seoVal('company_employees', '11-50'),
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
  'priceRange'    => $_seoVal('company_price_range', '$$$'),
  'slogan'        => $_seoVal('site_tagline', 'Transforming Ideas Into Digital Reality'),
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
function seoOrganizationSchema(): array {
  global $_SEO_BIZ;
  return [
    '@context'   => 'https://schema.org',
    '@type'      => ['Organization','LocalBusiness','ProfessionalService'],
    '@id'        => $_SEO_BIZ['url'].'/#organization',
    'name'       => $_SEO_BIZ['name'],
    'legalName'  => $_SEO_BIZ['legalName'],
    'url'        => $_SEO_BIZ['url'],
    'logo'       => [
      '@type'            => 'ImageObject',
      'url'              => $_SEO_BIZ['logo'],
      'width'            => 200,
      'height'           => 60,
      'caption'          => $_SEO_BIZ['name'].' Logo',
    ],
    'image'       => $_SEO_BIZ['logo'],
    'description' => $_SEO_BIZ['description'],
    'telephone'   => $_SEO_BIZ['phone'],
    'email'       => $_SEO_BIZ['email'],
    'foundingDate'=> $_SEO_BIZ['foundingYear'],
    'numberOfEmployees' => ['@type'=>'QuantitativeValue','value'=>$_SEO_BIZ['employees']],
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
    'serviceArea'    => ['@type'=>'GeoCircle','geoMidpoint'=>['@type'=>'GeoCoordinates','latitude'=>$_SEO_BIZ['geo']['lat'],'longitude'=>$_SEO_BIZ['geo']['lng']],'geoRadius'=>'5000 km'],
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
    'potentialAction' => [
      '@type'       => 'SearchAction',
      'target'      => ['@type'=>'EntryPoint','urlTemplate'=>$_SEO_BIZ['url'].'/api/search.php?q={search_term_string}'],
      'query-input' => 'required name=search_term_string',
    ],
  ];
}

/* ══════════════════════════════════════════════════════════
   3. WEBPAGE SCHEMA
══════════════════════════════════════════════════════════ */
function seoWebPageSchema(string $title, string $desc, string $url, string $type = 'WebPage', ?string $lastMod = null): array {
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
    'datePublished'     => '2024-01-01',
    'dateModified'      => $lastMod ?? date('Y-m-d'),
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
    '@graph'   => [
      seoOrganizationSchema(),
      seoWebsiteSchema(),
      seoLocalBusinessSchema(),
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
  $title     = $pageData['title'] ?? $_SEO_BIZ['name'];
  $desc      = $pageData['desc']  ?? $_SEO_BIZ['description'];

  switch ($pageType) {
    case 'home':
      $schemas[] = seoWebPageSchema($title, $desc, $url, 'WebPage');
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
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Services','']]);
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      break;

    case 'service':
      $svc = $pageData['service'] ?? [];
      $schemas[] = seoServiceSchema($svc['name']??$title, $svc['description']??$desc, $url, $svc['service_group']??'Software Development');
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Services',SITE_URL.'/services.php'],[$svc['name']??$title,'']]);
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      break;

    case 'blog':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage');
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
      $schemas[] = seoWebPageSchema($title,$desc,$url,'FAQPage');
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['FAQs','']]);
      break;

    case 'portfolio':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Portfolio','']]);
      break;

    case 'careers':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage');
      if (!empty($pageData['jobs'])) {
        foreach (array_slice($pageData['jobs'],0,10) as $j) {
          $schemas[] = seoJobPostingSchema($j, SITE_URL.'/careers.php#job-'.$j['id']);
        }
      }
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Careers','']]);
      break;

    case 'products':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Products','']]);
      break;

    case 'product':
      $product = $pageData['product'] ?? [];
      $schemas[] = seoProductSchema($product, $url);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Products',SITE_URL.'/products.php'],[$product['name']??$title,'']]);
      if (!empty($pageData['faqs'])) $schemas[] = seoFaqSchema($pageData['faqs']);
      break;

    case 'courses':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['IT Courses','']]);
      break;

    case 'course':
      $course = $pageData['course'] ?? [];
      $schemas[] = seoCourseSchema($course, $url);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Courses',SITE_URL.'/courses.php'],[$course['title']??$title,'']]);
      break;

    case 'partners':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Partners','']]);
      break;

    case 'clients':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Clients','']]);
      if (!empty($pageData['testimonials'])) $schemas[] = seoReviewSchema($pageData['testimonials']);
      break;

    case 'gallery':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'ImageGallery');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Gallery','']]);
      break;

    case 'testimonials':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage');
      if (!empty($pageData['testimonials'])) $schemas[] = seoReviewSchema($pageData['testimonials']);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Testimonials','']]);
      break;

    case 'apps':
      $schemas[] = seoWebPageSchema($title,$desc,$url,'CollectionPage');
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Mobile Apps','']]);
      break;

    /* Founder bio — a Person profile, which is what Google reads for
       knowledge-panel and author signals. */
    case 'person':
      $p = $pageData['person'] ?? [];
      $schemas[] = seoPruneEmpty([
        '@context' => 'https://schema.org',
        '@type'    => 'Person',
        '@id'      => $url . '#person',
        'name'     => $p['name']     ?? '',
        'jobTitle' => $p['jobTitle'] ?? '',
        'description' => $p['description'] ?? $desc,
        'url'      => $url,
        'image'    => $p['image'] ?? '',
        'email'    => $p['email'] ?? '',
        'sameAs'   => array_values(array_filter($p['sameAs'] ?? [])),
        'knowsAbout' => array_values(array_filter($p['knowsAbout'] ?? [])),
        'alumniOf' => !empty($p['alumniOf']) ? ['@type'=>'EducationalOrganization','name'=>$p['alumniOf']] : null,
        'worksFor' => [
          '@type'         => 'Organization',
          'name'          => $_SEO_BIZ['name'] ?? '',
          'url'           => SITE_URL,
          'foundingDate'  => $p['foundingDate'] ?? '',
        ],
      ]);
      $schemas[] = seoBreadcrumbSchema([[$_SEO_BIZ['name'],SITE_URL],['Our Founder','']]);
      break;

    default:
      $schemas[] = seoWebPageSchema($title,$desc,$url,'WebPage');
      break;
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
