<?php
/**
 * Appsgain Technologies — Master HTML Email Template System
 * Renders beautiful, cross-client compatible HTML emails
 * Compatible: Gmail, Outlook 2016+, Apple Mail, Yahoo Mail, iOS/Android
 */

if (!defined('SITE_URL')) { die('Direct access not allowed'); }

/**
 * Render a complete HTML email
 *
 * @param array $opts {
 *   string  $subject        Email subject line
 *   string  $preheader      Preview text shown in inbox (hidden in email body)
 *   string  $heading        Main heading inside email
 *   string  $subheading     Sub-heading below main heading (optional)
 *   string  $body           HTML body content (paragraphs, lists, etc.)
 *   string  $cta_text       Primary button text (optional)
 *   string  $cta_url        Primary button URL (optional)
 *   string  $cta_color      Button background hex (default: #6a00ff)
 *   string  $cta2_text      Secondary button text (optional)
 *   string  $cta2_url       Secondary button URL (optional)
 *   string  $accent_color   Top bar + heading accent hex (default: #6a00ff)
 *   string  $type           'success'|'info'|'warning'|'error'|'plain' (default: 'info')
 *   array   $info_rows      [['label'=>'...','value'=>'...'], ...] — info table rows
 *   string  $footer_extra   Extra HTML in the footer (optional)
 *   bool    $show_social    Show social icons in footer (default: true)
 * }
 */
function renderEmailHtml(array $opts): string {
    $s = getAllSettings();

    /* ── Config ── */
    $siteName  = $s['site_name']     ?? 'Appsgain Technologies';
    $siteUrl   = defined('SITE_URL') ? SITE_URL : ($s['site_url'] ?? '');
    $logoUrl   = !empty($s['site_logo']) ? UPLOADS_URL . '/' . $s['site_logo'] : '';
    $phone     = $s['site_phone']    ?? $s['phone']    ?? '+91-9955446477';
    $email     = $s['site_email']    ?? $s['email']    ?? 'info@appsgain.in';
    $address   = $s['site_address']  ?? $s['address']  ?? 'Electronic City Phase 1, Bangalore – 560100, India';
    $fbUrl     = $s['site_facebook'] ?? $s['facebook_url']  ?? '';
    $liUrl     = $s['site_linkedin'] ?? $s['linkedin_url']  ?? '';
    $igUrl     = $s['site_instagram']?? $s['instagram_url'] ?? '';
    $twUrl     = $s['site_twitter']  ?? $s['twitter_url']   ?? '';

    /* ── Options ── */
    $subject    = $opts['subject']     ?? '';
    $preheader  = $opts['preheader']   ?? $subject;
    $heading    = $opts['heading']     ?? 'Hello from Appsgain Technologies';
    $subheading = $opts['subheading']  ?? '';
    $body       = $opts['body']        ?? '';
    $ctaText    = $opts['cta_text']    ?? '';
    $ctaUrl     = $opts['cta_url']     ?? '';
    $cta2Text   = $opts['cta2_text']   ?? '';
    $cta2Url    = $opts['cta2_url']    ?? '';
    $infoRows   = $opts['info_rows']   ?? [];
    $footerExtra= $opts['footer_extra']?? '';
    $showSocial = $opts['show_social'] ?? true;

    $type = $opts['type'] ?? 'info';
    $typeColors = [
        'success' => ['#059669', '#ecfdf5', '#065f46'],
        'error'   => ['#dc2626', '#fef2f2', '#991b1b'],
        'warning' => ['#5500cc', '#f7f8fc', '#92400e'],
        'info'    => ['#5500cc', '#f7f9fc', '#1e3a8a'],
        'plain'   => ['#6a00ff', '#f7f9fc', '#38007d'],
    ];
    $tc = $typeColors[$type] ?? $typeColors['info'];

    $accentColor = $opts['accent_color'] ?? '#6a00ff';
    $ctaColor    = $opts['cta_color']    ?? $accentColor;
    $ctaHover    = $opts['cta_hover']    ?? $accentColor;

    $year = date('Y');

    /* ── Inline icon SVGs for email clients ── */
    $phoneIcon = '📞';
    $emailIcon = '📧';
    $mapIcon   = '📍';

    ob_start();
?><!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="x-apple-disable-message-reformatting">
  <title><?= htmlspecialchars($subject) ?></title>
  <!--[if mso]>
  <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
  <![endif]-->
  <style>
    /* ── Reset ── */
    * { box-sizing: border-box; }
    body { margin: 0 !important; padding: 0 !important; background-color: #f7f9fc !important; }
    table { border-collapse: collapse !important; }
    img { border: 0; max-width: 100%; height: auto; }
    a { text-decoration: none; }

    /* ── Mobile ── */
    @media screen and (max-width: 600px) {
      .email-container { width: 100% !important; }
      .email-body-pad { padding: 28px 20px !important; }
      .email-header-pad { padding: 28px 20px !important; }
      .btn-full { width: 100% !important; display: block !important; text-align: center !important; }
      .hide-mob { display: none !important; }
      .show-mob { display: block !important; }
      .info-row-val { padding-left: 0 !important; }
      h1.email-heading { font-size: 22px !important; line-height: 1.3 !important; }
      .footer-pad { padding: 24px 20px !important; }
      .social-icon-wrap { display: inline-block !important; }
    }
    @media (prefers-color-scheme: dark) {
      .dark-hide { display: none !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f7f9fc;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<!-- Preheader (hidden preview text) -->
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#f7f9fc;line-height:1px;max-width:0;opacity:0;">
  <?= htmlspecialchars($preheader) ?>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌
</div>

<!-- Wrapper -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f9fc;padding:24px 16px;" border="0">
  <tr>
    <td align="center">

      <!-- ════════════ MAIN CONTAINER ════════════ -->
      <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(15,23,62,.14);" border="0">

        <!-- ── TOP BRAND BAR ── -->
        <tr>
          <td style="background:linear-gradient(90deg,<?= htmlspecialchars($accentColor) ?> 0%,#8033ff 60%,#6a00ff 100%);height:4px;font-size:4px;line-height:4px;">&nbsp;</td>
        </tr>

        <!-- ── HEADER / LOGO ── -->
        <tr>
          <td class="email-header-pad" style="background:linear-gradient(150deg,#040a1f 0%,#0d1b3e 50%,#1a1260 100%);padding:32px 40px;text-align:center;">

            <!-- Logo -->
            <?php if ($logoUrl): ?>
            <a href="<?= htmlspecialchars($siteUrl) ?>" style="display:inline-block;margin-bottom:16px;">
              <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($siteName) ?>" width="160" style="max-width:160px;height:auto;">
            </a>
            <?php else: ?>
            <a href="<?= htmlspecialchars($siteUrl) ?>" style="display:inline-block;text-decoration:none;margin-bottom:16px;">
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;" border="0">
                <tr>
                  <td style="background:linear-gradient(135deg,<?= htmlspecialchars($accentColor) ?>,#8033ff);border-radius:14px;width:46px;height:46px;text-align:center;vertical-align:middle;">
                    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:18px;font-weight:900;color:#ffffff;letter-spacing:-1px;">AG</span>
                  </td>
                  <td style="padding-left:12px;vertical-align:middle;text-align:left;">
                    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:18px;font-weight:900;color:#ffffff;letter-spacing:-0.3px;display:block;line-height:1.2;">Appsgain <span style="color:#c4a5ff;">Technologies</span></span>
                    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:10px;font-weight:500;color:rgba(255,255,255,0.4);letter-spacing:1.5px;text-transform:uppercase;display:block;margin-top:2px;">Software & Digital Solutions</span>
                  </td>
                </tr>
              </table>
            </a>
            <?php endif; ?>

            <!-- Heading -->
            <?php if ($heading): ?>
            <h1 class="email-heading" style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:26px;font-weight:800;color:#ffffff;line-height:1.25;margin:0 0 8px;letter-spacing:-0.5px;">
              <?= $heading ?>
            </h1>
            <?php endif; ?>
            <?php if ($subheading): ?>
            <p style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;color:rgba(255,255,255,0.6);line-height:1.65;margin:0;">
              <?= $subheading ?>
            </p>
            <?php endif; ?>

          </td>
        </tr>

        <!-- ── TYPE BANNER (success / error / warning / info) ── -->
        <?php if ($type !== 'plain'): ?>
        <tr>
          <td style="background:<?= htmlspecialchars($tc[1]) ?>;padding:14px 40px;border-bottom:1px solid <?= htmlspecialchars($tc[0]) ?>22;">
            <p style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;font-weight:700;color:<?= htmlspecialchars($tc[2]) ?>;margin:0;text-align:center;letter-spacing:0.3px;">
              <?php
              $typeIcons = ['success'=>'✅','error'=>'❌','warning'=>'⚠️','info'=>'ℹ️'];
              $typeLabels = ['success'=>'Action Successful','error'=>'Action Required','warning'=>'Attention Needed','info'=>'Information'];
              echo ($typeIcons[$type] ?? 'ℹ️') . '&nbsp;&nbsp;' . ($typeLabels[$type] ?? '');
              ?>
            </p>
          </td>
        </tr>
        <?php endif; ?>

        <!-- ── MAIN BODY ── -->
        <tr>
          <td class="email-body-pad" style="background:#ffffff;padding:40px;">

            <!-- Body content -->
            <?php if ($body): ?>
            <div style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;color:#242a42;line-height:1.8;">
              <?= $body ?>
            </div>
            <?php endif; ?>

            <!-- Info table rows -->
            <?php if (!empty($infoRows)): ?>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0;border-radius:12px;overflow:hidden;border:1px solid #e7e9f0;" border="0">
              <?php foreach ($infoRows as $row): ?>
              <tr>
                <td style="padding:11px 16px;background:#fbfcfe;border-bottom:1px solid #e7e9f0;width:36%;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;color:#5e6475;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap;vertical-align:top;">
                  <?= htmlspecialchars($row['label'] ?? '') ?>
                </td>
                <td class="info-row-val" style="padding:11px 16px;background:#ffffff;border-bottom:1px solid #e7e9f0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;color:#0b1026;font-weight:500;vertical-align:top;word-break:break-word;">
                  <?= $row['value'] ?? '' ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <!-- Primary CTA Button -->
            <?php if ($ctaText && $ctaUrl): ?>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px auto 0;" border="0">
              <tr>
                <td style="text-align:center;">
                  <!--[if mso]>
                  <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?= htmlspecialchars($ctaUrl) ?>" style="height:48px;v-text-anchor:middle;width:220px;" arcsize="23%" strokecolor="<?= htmlspecialchars($ctaColor) ?>" fillcolor="<?= htmlspecialchars($ctaColor) ?>">
                    <w:anchorlock/>
                    <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:bold;"><?= htmlspecialchars($ctaText) ?></center>
                  </v:roundrect>
                  <![endif]-->
                  <!--[if !mso]><!-->
                  <a href="<?= htmlspecialchars($ctaUrl) ?>" class="btn-full"
                     style="display:inline-block;background:linear-gradient(135deg,<?= htmlspecialchars($ctaColor) ?>,<?= htmlspecialchars($ctaColor) ?>cc);color:#ffffff;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;font-weight:800;text-decoration:none;padding:14px 32px;border-radius:12px;text-align:center;letter-spacing:0.2px;min-width:200px;box-shadow:0 4px 18px <?= htmlspecialchars($ctaColor) ?>55;">
                    <?= htmlspecialchars($ctaText) ?> &nbsp;→
                  </a>
                  <!--<![endif]-->
                </td>
              </tr>
            </table>
            <?php endif; ?>

            <!-- Secondary CTA -->
            <?php if ($cta2Text && $cta2Url): ?>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:14px auto 0;" border="0">
              <tr>
                <td style="text-align:center;">
                  <a href="<?= htmlspecialchars($cta2Url) ?>"
                     style="display:inline-block;color:<?= htmlspecialchars($accentColor) ?>;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13.5px;font-weight:700;text-decoration:none;padding:10px 24px;border-radius:10px;border:2px solid <?= htmlspecialchars($accentColor) ?>33;min-width:160px;text-align:center;">
                    <?= htmlspecialchars($cta2Text) ?>
                  </a>
                </td>
              </tr>
            </table>
            <?php endif; ?>

          </td>
        </tr>

        <!-- ── CONTACT STRIP ── -->
        <tr>
          <td style="background:#fbfcfe;border-top:1px solid #e7e9f0;border-bottom:1px solid #e7e9f0;padding:20px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="text-align:center;">
                  <table role="presentation" cellpadding="0" cellspacing="0" style="display:inline-table;margin:0 auto;" border="0">
                    <tr>
                      <td style="padding:0 14px;border-right:1px solid #e7e9f0;text-align:center;">
                        <span style="font-size:11px;color:#8b90a0;font-family:sans-serif;display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:0.8px;">Phone</span>
                        <a href="tel:<?= htmlspecialchars(preg_replace('/[^+0-9]/', '', $phone)) ?>" style="font-size:13px;font-weight:700;color:#5500cc;font-family:sans-serif;text-decoration:none;"><?= htmlspecialchars($phone) ?></a>
                      </td>
                      <td style="padding:0 14px;text-align:center;">
                        <span style="font-size:11px;color:#8b90a0;font-family:sans-serif;display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:0.8px;">Email</span>
                        <a href="mailto:<?= htmlspecialchars($email) ?>" style="font-size:13px;font-weight:700;color:#6a00ff;font-family:sans-serif;text-decoration:none;"><?= htmlspecialchars($email) ?></a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ── FOOTER ── -->
        <tr>
          <td class="footer-pad" style="background:linear-gradient(150deg,#040a1f 0%,#0d1b3e 100%);padding:28px 40px;text-align:center;border-radius:0 0 20px 20px;">

            <!-- Social icons -->
            <?php if ($showSocial && ($fbUrl || $liUrl || $igUrl || $twUrl)): ?>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 18px;" border="0">
              <tr>
                <?php
                $socials = [
                  $fbUrl => ['fb',  '#1877f2', 'f'],
                  $liUrl => ['in',  '#0a66c2', 'in'],
                  $igUrl => ['ig',  '#e1306c', 'ig'],
                  $twUrl => ['tw',  '#1a1a1a', 'tw'],
                ];
                foreach ($socials as $url => [$id, $bg, $lbl]):
                  if (!$url || $url === '#') continue;
                ?>
                <td style="padding:0 5px;">
                  <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener"
                     style="display:inline-block;width:34px;height:34px;background:<?= $bg ?>;border-radius:8px;text-align:center;line-height:34px;color:#ffffff;font-family:sans-serif;font-size:12px;font-weight:800;text-decoration:none;"><?= strtoupper($lbl) ?></a>
                </td>
                <?php endforeach; ?>
              </tr>
            </table>
            <?php endif; ?>

            <!-- Company name -->
            <p style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;font-weight:800;color:#ffffff;margin:0 0 4px;letter-spacing:-0.2px;">
              Appsgain <span style="color:#c4a5ff;">Technologies</span>
            </p>
            <p style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11.5px;color:rgba(255,255,255,0.4);margin:0 0 12px;line-height:1.6;">
              <?= htmlspecialchars($address) ?>
            </p>

            <!-- Footer extra content -->
            <?php if ($footerExtra): ?>
            <p style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;color:rgba(255,255,255,0.35);margin:8px 0;line-height:1.6;">
              <?= $footerExtra ?>
            </p>
            <?php endif; ?>

            <!-- Legal -->
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:14px auto 0;" border="0">
              <tr>
                <td style="padding:0 8px;border-right:1px solid rgba(255,255,255,0.1);">
                  <a href="<?= htmlspecialchars($siteUrl) ?>/privacy-policy.php" style="font-size:11px;color:rgba(255,255,255,0.3);text-decoration:none;font-family:sans-serif;">Privacy Policy</a>
                </td>
                <td style="padding:0 8px;border-right:1px solid rgba(255,255,255,0.1);">
                  <a href="<?= htmlspecialchars($siteUrl) ?>/terms-of-service.php" style="font-size:11px;color:rgba(255,255,255,0.3);text-decoration:none;font-family:sans-serif;">Terms</a>
                </td>
                <td style="padding:0 8px;">
                  <a href="<?= htmlspecialchars($siteUrl) ?>/contact.php" style="font-size:11px;color:rgba(255,255,255,0.3);text-decoration:none;font-family:sans-serif;">Contact Us</a>
                </td>
              </tr>
            </table>

            <p style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;color:rgba(255,255,255,0.18);margin:16px 0 0;line-height:1.5;">
              &copy; <?= $year ?> Appsgain Technologies. All rights reserved.<br>
              You received this email because you interacted with our website.
            </p>

          </td>
        </tr>

        <!-- ── BOTTOM ACCENT BAR ── */-->
        <tr>
          <td style="background:linear-gradient(90deg,<?= htmlspecialchars($accentColor) ?> 0%,#8033ff 50%,#6a00ff 100%);height:3px;font-size:3px;line-height:3px;">&nbsp;</td>
        </tr>

      </table>
      <!-- /main container -->

      <!-- Below email note -->
      <p style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;color:#8b90a0;text-align:center;margin:16px 0 0;line-height:1.6;">
        This email was sent by Appsgain Technologies &bull; <a href="<?= htmlspecialchars($siteUrl) ?>" style="color:#8b90a0;"><?= htmlspecialchars(preg_replace('#https?://#', '', $siteUrl)) ?></a>
      </p>

    </td>
  </tr>
</table>
</body>
</html>
<?php
    return ob_get_clean();
}

/**
 * Send an email using the beautiful template
 *
 * @param string $to      Recipient email
 * @param string $subject Subject line
 * @param array  $opts    Same as renderEmailHtml options
 * @return bool
 */
function sendTemplatedEmail(string $to, string $subject, array $opts): bool {
    $opts['subject'] = $subject;
    $html    = renderEmailHtml($opts);
    $s       = getAllSettings();

    /* Settings -> Mail collects SMTP credentials. They used to be stored
       and ignored: every message went out through a bare mail() call with
       a hardcoded sender. Use them when they are filled in, and keep
       mail() as the fallback so nothing breaks if they are not. */
    require_once __DIR__ . '/smtp.php';
    $cfg = smtpConfigFromSettings();
    if ($cfg['host'] !== '') {
        $res = smtpSend($to, $subject, $html, $cfg);
        if ($res !== null) {
            if (!$res['ok'] && function_exists('error_log')) {
                error_log('[mail] SMTP send failed for ' . $to . ': ' . $res['error']);
            }
            return $res['ok'];
        }
    }

    /* No SMTP configured — fall back, honouring the From settings. */
    $fromAddr = $cfg['from'] ?: 'noreply@appsgain.in';
    $fromName = $cfg['from_name'] ?: 'Appsgain Technologies';
    $from    = sprintf('%s <%s>', $fromName, $fromAddr);
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: quoted-printable',
        'From: ' . $from,
        'Reply-To: ' . ($s['site_email'] ?? 'info@appsgain.in'),
        'X-Mailer: Appsgain-Mailer/2.0',
        'X-Priority: 1',
    ]);
    return @mail($to, $subject, quoted_printable_encode($html), $headers);
}
