<?php
// Let's get the server IP
$serverIP = $_SERVER['SERVER_ADDR'];

// Pass the server IP to the API to check if it is a BigScoots IP
$ch = curl_init("https://www.bigscoots.com/hosting-server-check/server-check.php?ip={$serverIP}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode( curl_exec($ch), true );
curl_close($ch);

function siteURL() {
  if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
    $url = "https://";   
  else  
    $url = "http://";   
  // Append the host(domain name, ip) to the URL.   
  $url.= $_SERVER['HTTP_HOST'];   

  // Append the requested resource location to the URL   
  $url.= $_SERVER['REQUEST_URI'];    

  return $url;  
}

function getIdentifier() {
  // Get the hostname (subdomain)
  $hostname = gethostname();
  
  // Extract just the subdomain (first part before the first dot)
  $parts = explode('.', $hostname);
  $subdomain = $parts[0];
  
  // Get the server's IP address
  $serverIP = $_SERVER['SERVER_ADDR'];
  
  // Extract the last octet of the IP
  $ipParts = explode('.', $serverIP);
  $lastOctet = end($ipParts);
  
  // Combine subdomain and last octet
  return $subdomain . '-' . $lastOctet;
}
?>
<!DOCTYPE html>
<html lang="en" class="has-background-light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BigScoots Migration Status</title>
  <meta name="theme-color" content="#597cff">
        <meta name="msapplication-navbutton-color" content="#597cff">
        <meta name="apple-mobile-web-app-status-bar-style" content="#597cff">
  <meta name="description" content="Check in on the status of your BigScoots migration."/>
  <meta property="og:locale" content="en_US">
        <meta property="og:title" content="BigScoots Migration Status">
        <meta property="og:url" content="<?php echo siteURL(); ?>">
        <meta property="og:site_name" content="BigScoots Migration Status">
        <meta property="og:type" content="website">
        <meta property="og:image" content="https://bigscoots.com/images/bigscoots_social.png">
        <meta property="og:description" content="Check in on the status of your BigScoots migration.">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:site" content="@BigScoots">
  <meta name="robots" content="noindex, nofollow">
        <meta property="twitter:title" content="BigScoots Migration Status">
        <meta property="twitter:image" content="https://bigscoots.com/images/bigscoots_social.png">
        <meta property="twitter:description" content="Check in on the status of your BigScoots migration.">
  <link rel="shortcut icon" href="https://www.bigscoots.com/favicon.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css" integrity="sha256-aPeK/N8IHpHsvPBCf49iVKMdusfobKo2oxF8lRruWJg=" crossorigin="anonymous">
  <style>
    @keyframes stroke {
      100% {
        stroke-dashoffset: 0;
      }
    }
    @keyframes stroke {
      100% {
        stroke-dashoffset: 0;
      }
    }

    @keyframes scale {
      0%, 100% {
        transform: none;
      }
      50% {
        transform: scale3d(1.1, 1.1, 1);
      }
    }

    @keyframes fill {
      100% {
        box-shadow: inset 0px 0px 0px 30px #00D1B2;
      }
    }
    body {
      position: relative;
    }
    .center-item {
      margin-right: auto;
      margin-left: auto;
      display: block;
    }
    .bg-section {
      background: url('https://www.bigscoots.com/hosting-server-check/server-img-compressed.jpg') no-repeat;
      background-size: cover;
      height: 50vh;
    }
    .bg-section .hero-body {
      background-color: rgba(89, 124, 255, .8);
    }
    .logo-container {
      display: flex;
      justify-content: center;
    }
    .details-container {
      position: absolute;
      width: 100%;
      top: 180px;
    }
    .item-details-col {
      margin-right: auto;
      margin-left: auto;
      border-radius: .9375rem;
      box-shadow: 0px 0px 10px 3px rgba(82, 82, 82, 0.3);
    }
    .animated-icon {
      margin-top: 30px;
      margin-bottom: 15px;
    }
    .checkmark {
      width: 80px;
      height: 80px;
      margin-right: auto;
      margin-left: auto;
      border-radius: 50%;
      display: block;
      stroke-width: 4;
      stroke: #fff;
      stroke-miterlimit: 10;
      box-shadow: inset 0px 0px 0px #00D1B2;
      animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    }
    .checkmark__circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 50;
      stroke-miterlimit: 10;
      stroke: #00D1B2;
      fill: none;
      animation: stroke .6s cubic-bezier(0.650, 0.000, 0.450, 1.000) forwards;
    }
    
    .checkmark__check {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      animation: stroke .3s cubic-bezier(0.650, 0.000, 0.450, 1.000) .8s forwards;
    }
    .cross__svg {
      border-radius: 50%;
      display: block;
      height: 100px;
      width: 100px;
      stroke-width: 3;
    }
    .cross__circle {
      animation: 0.6s ease 0s normal forwards 1 running stroke;
      fill: none;
      margin: 0 auto;
      stroke: #e55454;
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 2;
    }
    .cross__path {
      stroke: #e55454;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      transform-origin: 50% 50% 0;
    }
    .cross__path--right {
      animation: 0.3s ease 0.8s normal forwards 1 running stroke;
    }
    .cross__path--left {
      animation: 1s ease 0.8s normal forwards 1 running stroke;
    }
    .content-text {
      margin: 0 1.25rem 1.25rem 1.25rem;
    }
    .bigscoots-mascot svg {
      width: 25%;
    }
  .hostname-footer {
      text-align: center;
      margin-top: 15px;
      padding: 10px;
      font-size: 11px;
      color: #999;
      font-family: 'Courier New', monospace;
  }
  </style>
</head>
<body>
  <section class="hero bg-section">
    <div class="hero-body">
      <div class="logo-container">
        <a href="https://www.bigscoots.com/">
          <svg id="logo-light" version="1.1" width="300px" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 373.1 84" style="enable-background:new 0 0 373.1 84;" xml:space="preserve">
            <g>
              <g>
                <path fill="#FFFFFF" d="M156.7,31.4c-7.8,0-13.9,6.3-13.9,14.1c0,7.7,6.1,14,13.9,14c7.8,0,13.9-6.3,13.9-14 C170.6,37.6,164.5,31.4,156.7,31.4z M156.7,53.7c-4.5,0-8.1-3.7-8.1-8.3c0-4.6,3.5-8.3,8.1-8.3c4.5,0,8.1,3.7,8.1,8.3 C164.8,50,161.2,53.7,156.7,53.7z"></path>
                <path fill="#FFFFFF" d="M192.9,31.4c-7.8,0-13.9,6.3-13.9,14.1c0,7.7,6.1,14,13.9,14c7.8,0,13.9-6.3,13.9-14 C206.8,37.6,200.7,31.4,192.9,31.4z M192.9,53.7c-4.5,0-8.1-3.7-8.1-8.3c0-4.6,3.5-8.3,8.1-8.3c4.5,0,8.1,3.7,8.1,8.3 C201,50,197.5,53.7,192.9,53.7z"></path>
                <path fill="#FFFFFF" d="M15.3,44.4c2.8-1.1,3.7-3.3,3.7-5.1c0-4.7-3.2-7.6-8.1-7.6H0.6c-0.3,0-0.6,0.3-0.6,0.6v26.1 C0,58.7,0.3,59,0.6,59h11.1c5.4,0,8.7-3.8,8.7-8.4C20.5,48.4,18.9,45.3,15.3,44.4z M5.8,36.7h4.5c1.7,0,2.6,1.2,2.6,2.9 c0,1.6-1,2.8-2.5,2.8H5.8V36.7z M11.4,53.9H5.8v-6.6h5.5c2.1,0,3.1,1.7,3.1,3.2C14.4,52.1,13.4,53.9,11.4,53.9z"></path>
                <path fill="#FFFFFF" d="M35.7,31.6h-4.5c-0.3,0-0.6,0.3-0.6,0.6v26.1c0,0.3,0.3,0.6,0.6,0.6h4.5c0.4,0,0.6-0.3,0.6-0.6V32.2 C36.3,31.9,36,31.6,35.7,31.6z"></path>
                <path fill="#FFFFFF" d="M73.1,44.6H60.3c-0.3,0-0.6,0.3-0.6,0.6v3.5c0,0.3,0.3,0.6,0.6,0.6H67c0,2.9-3.7,4.7-6.6,4.7 c-4.6,0-8.2-4-8.2-8.7c0-4.8,3.6-8.6,8.2-8.6c2.2,0,4.1,0.9,5.6,2.3c0.2,0.2,0.6,0.3,0.8,0l3.4-2.9c0.3-0.2,0.3-0.6,0-0.9 c-2.4-2.5-5.9-4-9.9-4c-7.9,0-14,6.3-14,14c0,7.7,6.1,14,14,14c7.7,0,13.1-4.3,13.3-14.1C73.7,44.9,73.4,44.6,73.1,44.6z"></path>
                <path fill="#FFFFFF" d="M94,43.1l-3.3-1c-2.7-0.8-2.9-2.1-2.9-2.8c0-1.6,1.5-2.7,3.5-2.7c2.1,0,3.3,1,3.5,2.6c0,0.3,0.3,0.5,0.6,0.5 h4.6c0.4,0,0.6-0.3,0.6-0.7c-0.3-4.8-4.2-7.7-9.2-7.7c-5.2,0-9.4,3.3-9.4,8.2c0,2.5,1,6,7.1,7.9l3.3,0.9c2.8,0.8,3.3,1.9,3.3,3.1 c0,1.8-1.7,2.9-4,2.9c-2.4,0-3.7-1.3-4-2.8C87.5,51.2,87.3,51,87,51h-4.7c-0.4,0-0.7,0.3-0.6,0.6c0.3,4.6,4.2,7.8,9.9,7.8 c4.8,0,9.8-2.6,9.9-8C101.5,48.8,100.6,45,94,43.1z"></path>
                <path fill="#FFFFFF" d="M134.6,53l-3.7-2.6c-0.3-0.2-0.6-0.1-0.8,0.2c-1.5,2-3.8,3.3-6.5,3.3c-4.6,0-7.9-3.8-7.9-8.5 c0-4.8,3.3-8.6,7.9-8.6c2.6,0,4.8,1.2,6.3,3.1c0.2,0.3,0.6,0.3,0.9,0.1l3.7-2.6c0.3-0.2,0.4-0.6,0.2-0.9c-2.4-3.2-6.4-5.3-11-5.3 c-7.8,0-13.7,6.3-13.7,14c0,7.7,5.9,14,13.7,14c4.7,0,8.7-2.2,11.2-5.5C135,53.6,134.9,53.2,134.6,53z"></path>
                <path fill="#FFFFFF" d="M234.2,31.6h-19.4c-0.3,0-0.6,0.3-0.6,0.6v4c0,0.3,0.3,0.6,0.6,0.6h6.8v21.5c0,0.3,0.3,0.6,0.6,0.6h4.6 c0.3,0,0.6-0.3,0.6-0.6V36.9h6.8c0.3,0,0.6-0.3,0.6-0.6v-4C234.8,31.9,234.5,31.6,234.2,31.6z"></path>
                <path fill="#FFFFFF" d="M255.1,43.1l-3.3-1c-2.7-0.8-2.9-2.1-2.9-2.8c0-1.6,1.5-2.7,3.5-2.7c2.1,0,3.3,1,3.5,2.6 c0,0.3,0.3,0.5,0.6,0.5h4.6c0.3,0,0.7-0.3,0.6-0.7c-0.3-4.8-4.2-7.7-9.2-7.7c-5.2,0-9.4,3.3-9.4,8.2c0,2.5,1,6,7.1,7.9l3.3,0.9 c2.8,0.8,3.3,1.9,3.3,3.1c0,1.8-1.7,2.9-4,2.9c-2.4,0-3.7-1.3-4-2.8c-0.1-0.3-0.3-0.5-0.6-0.5h-4.7c-0.4,0-0.6,0.3-0.6,0.6 c0.3,4.6,4.2,7.8,9.9,7.8c4.8,0,9.8-2.6,9.8-8C262.7,48.8,261.8,45,255.1,43.1z"></path>
              </g>
          
              <g>
                <path fill="#FFFFFF" d="M317.8,36.2c3.3,0.8,5.1,4.6,4.2,8.6c-1,4-4.4,6.6-7.6,5.8c-3.3-0.8-5.1-4.7-4.2-8.7 C311.2,38,314.6,35.4,317.8,36.2z"></path>
                <path fill="#FFFFFF" d="M319.8,66.8C319.8,66.8,319.8,66.8,319.8,66.8L319.8,66.8l0.6,0.2C320.2,66.9,320,66.9,319.8,66.8z"></path>
                <path fill="#FFFFFF" d="M329.3,0c-23.7,0-36.2,14.3-38.7,33.2c-1.4,11.7,7.2,26.8,13.3,28.7c-0.7-0.2-6.6-1.9-12.4-9.1 c3.9,16.5,16.2,28.5,37.9,28.5c0.8,0,1.5,0,2.3,0c3.1,2.3,5.7,3.1,6.8,2.7c-1.9,0.2-8.9-11.4-10.6-16.1c-0.4,0-0.9,0-1.3,0 c-2.1,0-4.1-0.3-6-0.9c-2.1,4-4.6,7.7-5.7,7.5c-1.2-0.2-2.4-5.1-2.9-9.9c2.4,1.2,5.1,2,8,2.2c-8.3-2.8-14.2-10.6-14.2-19.8 c0-1.5,0.2-3,0.5-4.6c-2.2-1.5-3.7-4-3.7-6.9c0-4.5,3.6-8.2,8.2-8.2c-2,3.9-3.3,8.2-3.7,12.3c0,0,0,0,0,0 c1.8-5.5,5.3-11.1,9.5-15.8c-0.2,0.5-0.3,1-0.4,1.4c-0.5,4.1,5.1,8.2,12.5,9.2c1.1,0.2,2.2,0.2,3.2,0.2c5.3,0,9.6-1.8,10.9-4.6 c1.5-3.1,5.3-11.3,12.4-11.3c4.6,0,7.6,1.7,10.1,3C359.5,8.8,347.9,0,329.3,0z M340.6,22.6c-0.2,0-0.4,0-0.6,0 c-2.9,0-5.2-2.3-5.2-5.2l0-0.7c-1.2,0.7-2.7,1.1-4.2,1.1c-4.6,0-8.3-3.7-8.3-8.3c0-0.5,0-1,0.1-1.5c0.1-0.4,0.6-0.5,0.8-0.1 c0.7,1.2,2,2.1,3.5,2.1c2.8,0,3.4-1.8,6.2-1.8c4.3,0,7.7,3.5,7.7,7.7V22.6z"></path>
                <path fill="#FFFFFF" d="M367.4,29.6c0.7,3.5,1.1,7.2,1.1,11c0,19.3-9.2,35.5-28.8,39.6c0.7-4.9,0.6-13.5-0.2-16.5 c0-0.1-0.1-0.2-0.1-0.3c2-1.5,3.7-3.5,5-5.6c-1.3,0.2-2.6,0.3-3.9,0.3c-5.3,0-10-1.7-13.2-4.4h0c2.6,1.2,5.6,1.8,8.8,1.8 c3.9,0,7.5-1,10.4-2.6c0,0,0,0,0,0c4.1-2.3,6.7-5.9,6.7-10c0-1.8-0.7-3.6-1.9-5c0,0,0,0,0,0c-0.9-1.1-2-2-3.4-2.7 c1.6,0.5,3,1.1,4.2,1.9c1.8-2,2.3-4.7,4.2-6.9c5.2-5.7,18,0,16.2-10C374.3,25.7,371.5,28.4,367.4,29.6z"></path>
                <path fill="#FFFFFF" d="M338.4,83.9L338.4,83.9L338.4,83.9C338.4,83.9,338.4,83.9,338.4,83.9z"></path>
              </g>
          
              <g>
                <path fill="#415AFF" d="M338.4,83.9L338.4,83.9L338.4,83.9C338.4,83.9,338.4,83.9,338.4,83.9z M319.8,66.8L319.8,66.8 C319.8,66.8,319.8,66.8,319.8,66.8c0.2,0.1,0.4,0.1,0.6,0.2L319.8,66.8z"></path>
          
                <g>
                  <path fill="#415AFF" d="M320.4,67c-0.2-0.1-0.4-0.1-0.6-0.2c0,0,0,0,0,0h0L320.4,67z"></path>
                </g>
              </g>
            </g>
          </svg>
        </a>
      </div>
    </div>
  </section>
  <section class="details-container">
    <div class="container">
      <div class="columns">
        <div class="column is-8-fullhd is-8-widescreen is-10-desktop is-10-tablet is-11-mobile has-background-white mb-6 item-details-col">
          <div class="animated-icon">
            <?php if( $response['success'] && $response['ip_type'] === 'BigScoots' ) : ?>
              <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
            <?php else: ?>
              <svg class="cross__svg center-item" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="cross__circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="cross__path cross__path--right" fill="none" d="M16,16 l20,20" />
                <path class="cross__path cross__path--right" fill="none" d="M16,36 l20,-20" />
              </svg>
            <?php endif; ?>
          </div>
          <div class="description">
            <?php if( $response['success'] && $response['ip_type'] === 'BigScoots' ) : ?>
              <h1 class="is-size-3 has-text-centered has-text-weight-bold mb-3">Hey there 👋</h1>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                If you are seeing this page, it is because <strong>your site is successfully loading on BigScoots!</strong> You can now get back to work and be confident knowing all your changes will be done on your new hosting service here at BigScoots.
              </p>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                Our team members are available 24/7 around-the-clock with ~5 minute response times and always standing by to help! Please reach out to us as often as you'd like, our experts will always be here ready and waiting. You can email <a href="mailto:support@bigscoots.com">support@bigscoots.com</a> or log into your account and submit a support ticket at any time!
              </p>
              <div class="content-link has-text-centered mb-5">
                <a href="/" class="button is-link" target="_blank" rel="internal">Get Back to Work!</a>
              </div>
              <div class="bigscoots-mascot">
                <svg xmlns="http://www.w3.org/2000/svg" class="center-item" viewBox="0 0 527.8 679.56"><defs><clipPath id="a" transform="translate(0 .01)"><path d="M84 152.9c-.1 0-.1 0-.2.1a10.18 10.18 0 01-11.9-8.4c-6-34.2 11.1-41.4 18.7-42.8 13.4-2.6 15.3 13.2 15.4 19.1l-9.3 1.1a5.2 5.2 0 00.3.5c-.2-.2-.6-.7-2.4-.4-.9.1-2.1.4-2.8 4s-.6 8.8.5 15a10.41 10.41 0 01-8.3 11.8z" fill="none"/></clipPath><clipPath id="b" transform="translate(0 .01)"><path d="M95.7 155.2h-.2a10.37 10.37 0 01-10.3-10.4c.1-34.7 18.2-38.8 25.9-38.9 13.6-.2 9.3 29.4 7.1 38.4l-6-14.2a1.55 1.55 0 00.2.6c-.2-.3.9-4-1-4.1-.9 0-2.1 0-3.5 3.5s-2.1 8.6-2.2 14.8a10 10 0 01-10 10.3z" fill="none"/></clipPath><clipPath id="c" transform="translate(0 .01)"><path d="M105.9 153.4c-10.4 2.1-23.8-5.2-25.9-16-2-10.5 1.4-15 6.9-16.1 6.2-1.2 11.1 4 16.9 7.8 6.5 4.1 14.3 4.2 15.8 12 2.9 14.8-1.1 23.4-10.4 36.1-3.4 4.6-22.5 8.2-28.4 2.4" fill="none"/></clipPath><radialGradient id="d" cx="246.89" cy="-407.52" r="394.94" gradientTransform="translate(0 1021.09)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2d24ac"/><stop offset="1" stop-color="#2d24ac" stop-opacity="0"/></radialGradient></defs><path d="M490.1 257.21a54.23 54.23 0 005.1-24c0-21.4-9.6-38-9.6-60.2 0-26.5 18.2-64.5 42.2-64.5 0 0-16-22-58.5-22-35 0-51 6.7-100 6.7-16.5 0-17.8-20.5-17.8-20.5s-55.9 3.9-55.9 46.3 27.5 77.5 8.8 103c0 0 54.1 57.8 161.7 59.7.2 0 20.1-13 24-24.5z" fill="#f9e17a"/><path d="M190 256.71s-33.2 32.8-69.9 40c-19.3 3.8-52.7-9.3-64-53.6-8.6-33.6 5.1-61.1 5.1-61.1l46.8-28.4s-4.6 15.3-1.3 31.9c3.1 16 11.8 23.4 20.1 21.8 22-.4 40.1-16.4 40.1-16.4" fill="#7add99"/><path d="M111.7 166.71c-2.1 6.7-3.4 11.5-2.4 16.7 2.5 12.9 6.8 25 17.5 23.9 17.7 82.6-36.9 91.2-36.9 91.2-66.4-49.5-31.8-115.8-31.8-115.8-14-3.7-19.8-54 31.4-64 39.4-7.8 22.2 48 22.2 48z" fill="#4167f2"/><path d="M87.5 126.91s4.3 3.5 8.6 2.6 10.7-4.3 9.8-8.7-15.1-4-15.1-4z" fill="#415aff" opacity=".5" style="isolation:isolate"/><path d="M63 182.91c-.7.3-2.4.3-4.9-.2 0 0-34.5 66.3 31.8 115.8a43.88 43.88 0 0013.7-5.4C36.8 248.41 63 182.91 63 182.91z" fill="#4770f4"/><path d="M118.8 205.01a97.81 97.81 0 00-17-10.3c-10-4.9-20.4-14.4-21-15 8.8 6.2 25 2.2 28.3-2.3a17.22 17.22 0 00.2 6c1.9 8.9 4.5 17.5 9.5 21.6z" fill="#3354f2" opacity=".75"/><path d="M106 153.51c-6.4 5.1-12.2 3.8-12.2 3.8-4.5 4.1-10.8 1.3-10.8 1.3-4.3 5.2-12.7 4-12.7 4-5.5 5.1-14.3 3.5-14.3 3.5a20 20 0 002.1-1 10.6 10.6 0 004.1-5.5 9.71 9.71 0 0012.9-9.2 10.26 10.26 0 008.9 2.7c.1 0 .1 0 .2-.1a11 11 0 005.4-2.9 24.35 24.35 0 0016.4 3.4z" fill="#3354f2" opacity=".5" style="isolation:isolate"/><path d="M85.9 148.71a22 22 0 00-4.4 2.3c-1 1-3.8 2.6-5.4 6.4-.8 1-2.6 6-1.3 13.9-1.1-4.3-.8-2.6-1.1-5.4a19.28 19.28 0 0111.5-19.7z" fill="#3354f2"/><path d="M75.4 111.91a20.69 20.69 0 00-13.7 2.2c-6.1 3.2-18.8 13.4-5.3 40.4 4.6 9.3 18.5 6 18.5-4.3a10.18 10.18 0 01-3-5.7c-2.9-16.1-.5-26.3 3.5-32.6z" fill="#4167f2"/><path d="M75.4 111.91a20.69 20.69 0 00-13.7 2.2c-6.1 3.2-18.8 13.4-5.3 40.4 4.6 9.3 18.5 6 18.5-4.3a10.18 10.18 0 01-3-5.7c-2.9-16.1-.5-26.3 3.5-32.6z" fill="none"/><path d="M74.9 150.21a9.6 9.6 0 01-6.8 9.3c4.7-2 2.1-10 1.5-19.6-.5-8 1.4-17.8 2.6-21h.1c-1.9 6.1-2.4 14.4-.4 25.6a10.18 10.18 0 003 5.7z" fill="#415aff" opacity=".75" style="isolation:isolate"/><path d="M75.4 111.91a20.69 20.69 0 00-13.7 2.2c-6.1 3.2-18.8 13.4-5.3 40.4 4.6 9.3 18.5 6 18.5-4.3a10.18 10.18 0 01-3-5.7c-2.9-16.1-.5-26.3 3.5-32.6z" fill="none"/><path d="M60.7 123.51c-2.1 5.3-3.6 10.7-7.8 11.5-1.3.2-2.2-1.5-2.4-4.1a20.31 20.31 0 0111.2-16.8 21.38 21.38 0 013.9-1.6c.2 1.2-2.9 6-4.9 11z" fill="#4770f4"/><path d="M56.4 154.61c-6.7-13.5-6.9-22.8-4.6-29a19.9 19.9 0 00-6.2 2.8c-3.8 2.6-15 12.5-1.2 33.8a9.82 9.82 0 0017.6-2.6 10.58 10.58 0 01-5.6-5z" fill="#4167f2"/><path d="M56.4 154.61c-6.7-13.5-6.9-22.8-4.6-29a19.9 19.9 0 00-6.2 2.8c-3.8 2.6-15 12.5-1.2 33.8a9.82 9.82 0 0017.6-2.6 10.58 10.58 0 01-5.6-5z" fill="none"/><path d="M56.4 154.61c-6.7-13.5-6.9-22.8-4.6-29a19.9 19.9 0 00-6.2 2.8c-3.8 2.6-15 12.5-1.2 33.8a9.82 9.82 0 0017.6-2.6 10.58 10.58 0 01-5.6-5z" fill="none"/><path d="M84 152.91c-.1 0-.1 0-.2.1a10.18 10.18 0 01-11.9-8.4c-6-34.2 11.1-41.4 18.7-42.8 13.4-2.6 15.3 13.2 15.4 19.1l-9.3 1.1a5.2 5.2 0 00.3.5c-.2-.2-.6-.7-2.4-.4-.9.1-2.1.4-2.8 4s-.6 8.8.5 15a10.41 10.41 0 01-8.3 11.8z" fill="#4167f2"/><path d="M84 152.91c-.1 0-.1 0-.2.1a10.18 10.18 0 01-11.9-8.4c-6-34.2 11.1-41.4 18.7-42.8 13.4-2.6 15.3 13.2 15.4 19.1l-9.3 1.1a5.2 5.2 0 00.3.5c-.2-.2-.6-.7-2.4-.4-.9.1-2.1.4-2.8 4s-.6 8.8.5 15a10.41 10.41 0 01-8.3 11.8z" fill="none"/><path d="M89.4 150.01a9.87 9.87 0 01-5.4 2.9c.3-.2 3.8-2.6.5-5.6-3.8-3.3-4.4-9.1-4.5-9.8 1.1 5.2 4.7 9.6 9.4 12.5z" fill="#415aff" opacity=".75" style="isolation:isolate"/><g clip-path="url(#a)"><path d="M89.4 102.01c10.2-3.3 13.8 8.9 5.6 21.2l2.8 4.5 14.3-6.4s-11.2-29.5-11.2-29.2-11.8 7.1-11.8 7.1z" fill="#415aff" opacity=".5" style="isolation:isolate"/></g><path d="M84 152.91c-.1 0-.1 0-.2.1a10.18 10.18 0 01-11.9-8.4c-6-34.2 11.1-41.4 18.7-42.8 13.4-2.6 15.3 13.2 15.4 19.1l-9.3 1.1a5.2 5.2 0 00.3.5c-.2-.2-.6-.7-2.4-.4-.9.1-2.1.4-2.8 4s-.6 8.8.5 15a10.41 10.41 0 01-8.3 11.8z" fill="none"/><path d="M82.3 114.51c-2.3 5.5-4 11.3-8.4 12.2-1.4.2-2.3-1.6-2.4-4.3 2.5-13 10.4-17.9 16.2-19.8.8.6-3 6.1-5.4 11.9z" fill="#4770f4"/><path d="M95.7 155.21h-.2a10.37 10.37 0 01-10.3-10.4c.1-34.7 18.2-38.8 25.9-38.9 19.3 3.7 9.3 29.4 7.1 38.4l-6-14.2a1.55 1.55 0 00.2.6c-.2-.3.9-4-1-4.1-.9 0-2.1 0-3.5 3.5s-2.1 8.6-2.2 14.8a10 10 0 01-10 10.3z" fill="#4167f2"/><g clip-path="url(#b)"><path d="M117.2 159.61c-10.6.2-22.5-9.3-22.7-20.3 0 0-.6 6.4 2.7 10.5 2.9 3.7-12.8-8-12.8-8l-.2 17.5 14.7.9z" fill="#415aff" opacity=".5" style="isolation:isolate"/></g><path d="M95.7 155.21h-.2a10.37 10.37 0 01-10.3-10.4c.1-34.7 18.2-38.8 25.9-38.9 19.6 4 9.3 29.4 7.1 38.4l-4.9-10.5s-2.2-1.6-2-1.2c-.2-.3 2.1-6 .3-6-.9 0-2.1 0-3.5 3.5s-2.1 8.6-2.2 14.8a10.33 10.33 0 01-10.2 10.3z" fill="none"/><path d="M119 141.51l-4-4.8-2.5-3-.7-5.7a3 3 0 00.5-.7c8.4-10.7 5.8-18.5-2.3-21.2v-.2h1.1c17.6 3.4 10.9 24.9 7.9 35.6z" fill="#415aff" opacity=".5" style="isolation:isolate"/><path d="M111.5 126.71c-.9 0-2.1 0-3.5 3.5s-2.1 8.6-2.2 14.8a10.24 10.24 0 01-10.2 10.2h-.2a10.37 10.37 0 01-10.3-10.4c.1-34.7 18.2-38.8 25.9-38.9 19.7 4.3 9.1 28.1 7.1 38.4l-8.2-12.2" fill="none"/><path d="M100.8 117.11c-3.3 5.1-5.9 10.4-10.4 10.5-1.5 0-2.1-2.2-1.6-5.1 4.7-11.7 12.9-15.3 18.8-16.3h.2c2.1 0-3.1 5.1-7 10.9z" fill="#4770f4"/><path d="M105.9 153.41c-10.4 2.1-23.8-5.2-25.9-16-2-10.5 1.4-15 6.9-16.1 6.2-1.2 11.1 4 16.9 7.8 6.5 4.1 14.3 4.2 15.8 12 2.9 14.8-1.1 23.4-10.4 36.1-3.4 4.6-22.5 8.2-28.4 2.4" fill="#4167f2"/><g clip-path="url(#c)"><path d="M90.6 119.81c1.8-.4-1.3 4.3-2.8 9.2-1.4 4.3-2.2 8.6-5.8 10.1s-9.2-15.8 8.6-19.3z" fill="#4167f2"/></g><path d="M53.6 166.51a9.89 9.89 0 008.4-6.9 10.58 10.58 0 01-5.6-5c-3.5-7-5.2-12.9-5.7-17.7l-.1-.1c.3 3.7 2.3 11.6 4.9 18.3 2 5.3 1.9 10.5-1.9 11.4z" fill="#415aff" opacity=".75" style="isolation:isolate"/><path d="M47.6 127.21a13.24 13.24 0 00-2 1.2c-2.9 2-10 8.2-7.3 20.3a1.32 1.32 0 001.5.5c3.5-1.4 4.1-6.5 5.1-11.5 1-4.6 2.9-9.2 2.7-10.5z" fill="#4770f4"/><path d="M112.2 130.11l.2.4v.2a3.93 3.93 0 01-.2-.6z" fill="#4167f2"/><path fill="#7add99" d="M190.6 506.01l-78.6-8-112 27 113.7 20.5 76.9-39.5z"/><path d="M239.5 502.51c-36.3-6.2-104-31.1-123-41.8 0 0-9.7-14-10.2-22.7 0 0 90.5-12.7 117.5-11z" fill="#4167f2"/><path d="M239.5 502.51c-36.3-6.2-104-31.1-123-41.8 0 0-9.7-14-10.2-22.7 0 0 90.5-12.7 117.5-11z" fill="#3354f2" opacity=".35" style="isolation:isolate"/><path d="M111.1 601.5c78.5-12.3 192 10.2 216.4 58.1 0 0 11.4 19.4 19.9 19.9s25.3-56 64.4-60.5 73.8-48.7 20-69.5c-80.2-31-120.7 17.2-172.8 17.2s-94.3-18.5-94.3-18.5z" transform="translate(0 .01)" opacity=".1" fill="url(#d)" style="isolation:isolate"/><path d="M202.4 141.81c-43.7 21.5-98.9 117.3-98.9 199.6 0 1.3.1 2.6.2 4 5.9 55.7 78.5 106.5 172.1 117.1 96 10.9 180-24.3 195.2-79.7 12.7-51.7 1.3-84.3-16.3-137-5.2-15.4-13.5-45.6-26.9-54.5z" fill="#33cc86"/><path d="M264.3 235.61c-2.8-13-15.4-22.5-25.6-29.8 14.7-3.5 13.2-22.9 1-28.1 5.6 8.9 2.5 20-7.8 23.2-4-3-6.8-5.5-7.3-7.8 0 0-11.5 18-4.1 52.4s31.7 50.5 50.2 46.5c-9.9-16.4-.3-27.8-6.4-56.4z" fill="#27b772" opacity=".35" style="isolation:isolate"/><path d="M267.9 153.91l-65.5-12.3c-43.7 21.5-98.9 117.3-98.9 199.6 0 1.3.1 2.6.2 4 4.3 40.7 44.2 78.8 102 100.5-8-11.6-25.4-45.8-25.4-124.7 0-76.5 70.7-144.5 70.7-144.5z" fill="#7add99" opacity=".35" style="isolation:isolate"/><path d="M151 471.21c-13.8-15.6-39.1-15-39.1-15 12.1 17.5 14.7 34.1 17.7 44.4-15.4 0-58 18.3-58 23.5 0 5 69 2 77.2-16.9 3.9-12.7.1-18.8 2.2-36z" fill="#3354f2"/><path d="M453.9 413.31a178.79 178.79 0 01-18.7 31.6c-84 66-267.3 37.5-316.4-49.1a185.7 185.7 0 01-5.7-21c23.4 43.2 85.9 78.8 162.5 87.5 76.9 8.7 145.9-12 178.3-49z" fill="#415aff"/><path d="M435.3 444.91a180.47 180.47 0 01-316.4-49.1c49 86.5 231.8 115.4 316.4 49.1z" fill="#4167f2"/><path d="M328.4 275.31c0-39.7-31.3-68.3-67.9-83.9l14.7-39.8 9.1 7.9 143.5 31.5c13.4 8.9 21.7 39.2 26.9 54.5 18.3 54.4 24.9 77.9 20.4 115.1a176.06 176.06 0 01-4 21.9c-16 57.8-106.7 94.2-210.4 77.6 74.9-4 94.4-49.7 94.4-104-.2-51.9-26.7-80.8-26.7-80.8z" fill="#27b772" opacity=".35" style="isolation:isolate"/><path d="M470.8 333.01l-61 11.7.4 59.4c14.9-.1 30.3 6.6 34.6 17.6h.8c12.6-11.3 21.4-24.6 25.4-39.2a176.06 176.06 0 004-21.9 195.86 195.86 0 001.4-20.4z" fill="#27b772"/><path d="M435.3 444.91a180.41 180.41 0 01-145.4 73.6 199.92 199.92 0 01-22-1.3c23.5-7.9 32.9-16.8 35.3-35.9 54 1.1 102-12.7 132.1-36.4z" fill="#415aff" opacity=".5" style="isolation:isolate"/><path d="M374.4 491.21c0 25.7-19.7 48.3-25 70.3-5.6 22.9-2.6 41.5-15.9 41.5-13 0-11.9-20.4-18.4-41.3s-20.5-42.2-20.5-50.4" fill="#4167f2"/><path d="M374.4 491.21c0 25.7-19.7 48.3-25 70.3-5.6 22.9-2.6 41.5-15.9 41.5-13 0-11.9-20.4-18.4-41.3s-20.5-42.2-20.5-50.4" fill="#415aff" opacity=".5" style="isolation:isolate"/><path d="M344 597.11c7.3-11.7 5.3-38.1 21.6-54.6 0 0-33-8.2-64 2 14.9 21.3 14.6 43.7 21.7 53.5-4 8.7-3.5 24.7-1.2 41.8 3.3 24.8 16.7 40.8 25.2 39.7 15.4-2 14.5-61.3-3.3-82.4z" fill="#3354f2"/><path d="M329.6 511.31c-2.2 13.1-6.7 23.5-14.1 22.3s-11.7-12.9-9.5-26 10.1-22.7 17.5-21.5 8.4 12.1 6.1 25.2z" fill="#4167f2"/><path d="M339.1 627.71c1.3 14.5 2.5 25.7-2.9 26.2s-10.8-10.9-12.1-25.3 2.1-26.6 7.5-27c5.4-.6 6.2 11.6 7.5 26.1z" fill="#415aff"/><path d="M225.9 193.71c-27.9 2.7-47.7 19.5-44.8 49.9 1.1 11.8-1.2 28.1-6.9 28.6 14.9-1.4 25.5-15.8 35.9-37.1 5.9-12 3.6-24.5 15.8-41.4z" fill="#27b772" opacity=".35" style="isolation:isolate"/><path d="M425.2 213.61c-5.2.6-9.7 3.9-12.6 6.6a9 9 0 01-4.6 2.2c-42.8 7-109.3-12-147.6-31.2l4.5-12.1a23.4 23.4 0 003.8.3s22.7 15.8 65.6 15.8c32.4 0 54.1-3.7 68.7-9.6.1 0 24.6 5.4 24.6 5.4 4 5.3 5.7 10 5.6 14.1 0 4.4-3.6 7.9-8 8.5z" fill="#27b772"/><path d="M207.8 118.51c46.2-47.7 98.5-77.2 132.5-77.2a81.19 81.19 0 0181.2 81.2c0 19.3 2.8 38.4 7.1 57.1 5.9 33.3-55.6 45.9-126.6 26.7-71.7-19.4-119-62.1-94.2-87.8z" fill="#ffd2cf"/><path d="M248.2 79.81a293.8 293.8 0 0128.5-19l4.2 5-27.8 18.7z" fill="#ffd2cf"/><path d="M306.1 150.61a9.24 9.24 0 01-.9.5c-.1.1-1 .3-1.1.4-7.5 1.9-14.5-7.6-10.6-13.4a20.29 20.29 0 001.8 2.6c.6 1.2 3.1 3.6 3.7 4.4a37.83 37.83 0 006.3 5 2.58 2.58 0 01.8.5z" fill="#e8acac"/><path d="M292.9 1.71a63.6 63.6 0 00-.5 13.1c2.9 45.3 53.1 73 93.4 49.4a42.75 42.75 0 0041.1 54.3v-33.4a33.29 33.29 0 00-33.3-33.3 40.67 40.67 0 00-40.7-40.7c-16.5 0-21.8 4.5-31.5 4.5-12.6 0-20.9-9.1-24.9-14.8a2 2 0 00-3.6.9z" fill="#415aff"/><path d="M318.8 86.01s10.1-.5 13.7 7a16.12 16.12 0 00-10 5.7s-4-4.6-3.7-12.7zm-43.6-15s8.4-.4 11.4 5.8a13.63 13.63 0 00-8.4 4.8 16.81 16.81 0 01-3-10.6z" fill="#35071e" opacity=".75" style="isolation:isolate"/><path d="M317.1 126.41a23.72 23.72 0 01-32.1-1.2 1 1 0 00-1.4 1.4c14.1 15.4 37.2 7.2 37.9 2.3.5-1.9-1.8-4.2-4.4-2.5z" fill="#a01755" opacity=".5" style="isolation:isolate"/><path d="M428.3 189.51c-9.7 35.2-104.2 34.1-175.2-1.7-21.4-12.7-16.6-34.8-16.6-34.8a23.26 23.26 0 0023.3 23.3s21 14.7 60.7 14.7c81 0 90.1-25 90.1-48.4l11.5-4.5c2.5 35 9.2 40.9 6.2 51.4z" fill="#efbbbb" opacity=".35" style="isolation:isolate"/><path d="M259.9 176.31a20.68 20.68 0 01-2.4.6 17.68 17.68 0 01-12.8-3.2c-4.4-2.8-8.6-9.2-8.6-14.6a14.8 14.8 0 01.7-6c4 12 11.5 19.3 23.1 23.2z" fill="#e8acac"/><path d="M384.6 44.71c0 17.2-23.7 21.6-36.3 21.6s-34.2-5.9-43.8-18.6a61.51 61.51 0 01-12.1-32.8 63.05 63.05 0 01.5-13.1 2 2 0 013.6-.9c4 5.6 12.3 14.8 24.9 14.8 9.8 0 15-4.5 31.5-4.5a41.33 41.33 0 0112.8 2c10.7 7.3 18.9 15.3 18.9 31.5z" fill="#4167f2"/><path d="M424.9 161.01c-2.2-11.1-14.3-18.3-14.3-18.3l11.5-4.5c.6 7.6 1.6 15.2 2.8 22.8z" fill="#efbbbb" opacity=".75" style="isolation:isolate"/><path d="M393.6 51.81a40.61 40.61 0 00-27.9-38.6c10.8 7.3 18.9 15.3 18.9 31.5a15.7 15.7 0 01-2.4 8.5 27.47 27.47 0 00-15.5-25c4.5 3.1 7 10.3 7.6 17.4a18.36 18.36 0 01-15.4 19.7c-23.4 3.7-45.9-6.5-54.4-17.7a62 62 0 0081.3 16.6 42.76 42.76 0 0041.2 54.3v-33.4a33.44 33.44 0 00-33.4-33.3z" fill="#3354f2"/><path d="M403.7 66.51c5 3.7 9.7 6.1 12.2 2.8 5.9-8-12.9-21-18.2-13.4-2.5 3.2 1 6.9 6 10.6z" fill="#4167f2"/><path d="M275.4 91.51c-6.4-2.3-18.3-7.3-23.6-12.8-1.5-7.6-.7-16.3-.1-21.2 7.5 1.6 24.2 5.7 38.6 13.2a109.14 109.14 0 01-14.9 20.8zm69.3 23.1a246.77 246.77 0 01-34.6-10.8c-.6-7.4-1.6-20.9-1.6-28.6 8.7 1.6 29 6 49.1 15.8-1.9 5.1-6.2 15.6-12.9 23.6z" fill="#fff" opacity=".35" style="isolation:isolate"/><path d="M335.6 82.11l-26.2 12.7c-.2-2.6-.4-5.3-.5-8l16.3-7.9c3.2 1 6.8 2 10.4 3.2z" fill="#fff" opacity=".5" style="isolation:isolate"/><path d="M437 442.51a176.14 176.14 0 0016.9-29.2c-32.4 37-101.4 57.7-178.2 49-76.6-8.7-139.2-44.3-162.5-87.5q1.35 6.15 3 12.3c59.5 79.6 224.5 108.2 320.8 55.4z" fill="#2e4eed"/><path d="M235.7 486.01c-31.8-1.8-57.2-19.2-57.2-19.2-7.7-18.6-8.6-40.5-8.6-40.5 10 6.7 31.5 15.1 54.3 16.5 2.1 11.8 6.3 31.7 11.5 43.2z" fill="#f9e17a"/><path d="M242.7 483.01l-7 3c-5.2-11.4-9.5-31.8-11.5-43.4-21.7-1.3-43.2-9.1-54.3-16.5l7-3s21.7 14.5 54.3 16.5c0 .1 5.6 31.3 11.5 43.4z" fill="#ffd442"/><path d="M193.8 436.91l-19.9 15.8c-.8-3.2-1.5-6.4-2-9.3l12.5-9.9c2.8 1.2 6 2.3 9.4 3.4zm15.5 3.9l-31.2 24.8c-.5-1.4-1.1-2.8-1.5-4.2l27.4-21.8a37.67 37.67 0 005.3 1.2z" fill="#fff" opacity=".2" style="isolation:isolate"/><path fill="#3354f2" d="M271.6 53.51l21.7-6.3 2.1 12.5-22.7-.5-1.1-5.7zm72.9 25.5l-23.9-13 8.9-12.6 18.9 19.9-3.9 5.7z"/><path d="M422.4 110.51c-9.2-3-44.8-13.7-57.1-17.4.9-2.6 1.5-4.7 1.9-6.2a196.83 196.83 0 00-59.3-20 8.62 8.62 0 00-13-2.9c-14.5-7.8-33.2-13.2-49.6-15.8-3.5 16.4-2.6 25.6-.9 34.3 6.5 8.7 23 15 33.3 18.4 7.7-8.2 15.5-17.6 21.6-29.8a2 2 0 01.8-.8c.2 0 .4 0 .4.1-.7 7.8 1.3 31.7 1.9 39.2 14.7 5.7 29.8 11.1 45.1 13.9a77.45 77.45 0 0014.7-22.7c11.5 3.4 48.5 14.5 57.6 17.5 10.8 3.6 16.8 18.7 16.1 30.8-.2 5.4 7.8 5.6 8 .4 1.1-17.6-8.4-34.7-21.5-39zm-148.9-19.9c-6.4-2.3-16.4-6.4-21.8-11.9-1.5-7.6-.7-16.3-.1-21.2 7.5 1.6 22.3 4.8 36.8 12.3a109.14 109.14 0 01-14.9 20.8zm27.1-20.4zm42.2 43.5a203.18 203.18 0 01-32.8-9.9c-.6-7.4-1.6-20.9-1.6-28.6 8.7 1.6 27.2 5.1 47.3 14.9-1.9 5.1-6.1 15.6-12.9 23.6z" fill="#253caf"/><path d="M367.2 86.91l-2.9-1.5c-1.5 15.1-18.9 37.5-18.9 37.5l2.2.4c9.6-10.2 15.8-22.8 19.6-36.4zm-73.9-10.1c-1.4 2.3-13.1 19.6-18 23.1l2.4.8c7.7-8.2 15.5-17.6 21.6-29.8a2 2 0 01.8-.8c-3.5-.6-5.4 4.4-6.8 6.7z" fill="#153196"/><path d="M419.9 118.11c10.8 3.6 16.8 18.7 16.1 30.8-.2 5.3 7.8 5.6 8 .4 1-17.2-8.2-33.9-20.9-38.6 3.1 1.5 2.9 5.5-.7 4.3l-58.6-18-1.4 3.6c11.5 3.5 48.4 14.5 57.5 17.5zm-56.1-21l1.5-4-1.5 4z" fill="#153196"/><path d="M406.8 117.91a15.49 15.49 0 110 21.9" fill="#ffd2cf"/><path d="M412.4 123.81a8.18 8.18 0 0110.1 11.3 7.49 7.49 0 002.6-5.7 7.58 7.58 0 00-12.7-5.6z" fill="#e8acac"/><path fill="#efbbbb" d="M300.1 82.61l-17.8-1 9.3 18.3"/><path d="M228 334.41a49.08 49.08 0 01-34 13.8 54.3 54.3 0 01-10.5-1.1c-65.4-13.4-46.2-132.8 24.4-119.4 14.9 3 26.6 11.4 33.9 24.3 14.5 26.1 6.8 63.1-13.8 82.4z" fill="#fff"/><path d="M212 218.11c-84.7-16.9-108.5 124.5-28.8 140.9a61.53 61.53 0 0012.7 1.3c1.5 0 3-.1 4.4-.2a58.22 58.22 0 0019.4-4.8c55.3-24.3 60.3-123.4-7.7-137.2zm16 116.3a49.08 49.08 0 01-34 13.8 54.3 54.3 0 01-10.5-1.1c-65.4-13.4-46.2-132.8 24.4-119.4 14.9 3 26.6 11.4 33.9 24.3 14.5 26.1 6.8 63.1-13.8 82.4z" fill="#f9e17a"/><path d="M261.8 292.41c-29-3.3-45.1-40-43.7-72.8 30.8 9.4 47.3 39.4 43.7 72.8z" fill="#f4bc2f" opacity=".25" style="isolation:isolate"/><path d="M231 335.41a49.08 49.08 0 01-34 13.8 54.3 54.3 0 01-10.5-1.1 47.52 47.52 0 01-7.6-2.2c1.5.4 3 .8 4.6 1.2a54.3 54.3 0 0010.5 1.1 49.08 49.08 0 0034-13.8c20.6-19.3 28.3-56.3 13.8-82.4a48.6 48.6 0 00-25.7-22 48.38 48.38 0 0128.7 23c14.5 26.1 6.8 63.1-13.8 82.4z" fill="#ffce43"/><path d="M184.6 325.61c28.8-25.5 62.2-50.4 99.4-60.4l-6.1-21.1-4-2s-60.7 33.7-85.4 56.7c-4.3-11.7-5.9-25.7-5.7-29.1l-4-2-17.6 3.4c4 26.9 19.4 52.5 19.4 52.5z" fill="#27b772"/><path d="M180.6 323.61c28.8-25.5 62.2-50.4 99.4-60.4l-6.1-21.1c-32.7 10.7-64.1 31.2-89.4 54.7-4.3-11.7-5.9-25.7-5.7-29.1l-21.6 1.4a120.44 120.44 0 0023.4 54.5z" fill="#7add99"/><path d="M224.8 193.81a1.27 1.27 0 00-.6.1 2.22 2.22 0 01.4-.7 1.42 1.42 0 00.2.6zm1.1-.1c-.2.3-.5.7-.7 1a3.55 3.55 0 01-.4-.9 3.75 3.75 0 011.1-.1z" fill="#ffce43"/><path d="M222.2 199.41c-.7-.4-1.1-2.1-1.9-2.3a75.27 75.27 0 00-6.1 27.1c2.4-8 4.7-17.3 8-24.8z" fill="#415aff" opacity=".5" style="isolation:isolate"/><path d="M225.2 194.61c-1.6 2.4-3 4.6-4.2 6.8a40.94 40.94 0 013.1-7.6 4.56 4.56 0 00.4-.8c.1.2.1.5.2.7a9 9 0 01.5.9z" fill="#27b772" opacity=".35" style="isolation:isolate"/><path d="M215 218.81c-1.3 5.8-2.4 11.1-4.9 16.3-10.4 21.3-21 35.7-35.9 37.1 5.7-.5 8-16.8 6.9-28.6-.9-9.8.5-18.3 3.8-25.2 10.6-2.6 23.5-1.6 30.1.4z" fill="#f4bc2f" opacity=".25" style="isolation:isolate"/><path d="M225.9 193.71a5.5 5.5 0 01-.7.9c-1.6 2.4-3 4.6-4.2 6.8a40.94 40.94 0 013.1-7.6 2 2 0 00.7-.1 4.05 4.05 0 001.1 0z" fill="#27b772" opacity=".35" style="isolation:isolate"/><path d="M225.9 193.71c-28 0-49.4 14.8-49.4 45.3 0 11.8-4 27.8-9.6 27.8 15 0 26.9-13.3 39.3-33.5 7-11.3 5.9-23.9 19.7-39.6z" fill="#4167f2"/><path d="M213.5 194.71a48 48 0 00-20.8 33.1s2.9-13.4 17.3-24c-2.2 9.3-12.6 47.9-37.8 62.4 14.6-2.9 26.2-20.1 34.1-32.9 7-11.3 5.9-23.9 19.7-39.7a81.88 81.88 0 00-12.5 1.1z" fill="#415aff" opacity=".5" style="isolation:isolate"/><path d="M254.8 284.01c-7.3-15.3 2.1-24.4-.8-50.1-2.3-19.8-30.3-33.1-31.2-40.6 0 0-11.6 14.8-8.1 45.6s23.5 47 40.1 45.1z" fill="#4167f2"/><ellipse cx="230.4" cy="186.89" rx="14.6" ry="16.5" transform="rotate(-55.81 230.424 186.888)" fill="#f9e17a"/><path d="M244 196.21c-6.1 9.1-20.7 7.2-26.7-2.2 14.5 9.9 30.9-4.8 21.3-19.2h.1c7.4 5.1 9.9 14.7 5.3 21.4z" fill="#ffd442"/><path d="M254.8 284.01c-7.3-15.3 2.1-24.4-.8-50.1-1-8.8-7.1-16.3-13.6-22.6 3.8 4.8 12.8 17 8.7 35.6a141.42 141.42 0 00-2.8 29.8c-7.9-.9-18.8-8.1-26.3-18 7.9 17.8 22.3 26.8 34.8 25.3z" fill="#415aff" opacity=".5" style="isolation:isolate"/><path fill="#f9e17a" d="M329.2 250.91l-4 4-39 121.4 4-2 39-123.4z"/><path fill="#ffd442" d="M290.2 374.31l-4 2 155.7 28.3 3-1-154.7-29.3z"/><path fill="#ffce43" d="M444.9 403.61l-154.7-29.3 39-123.4 148.7 69.5-33 83.2z"/><path fill="#f2f2f2" d="M450.9 394.61l-144.7-29.3 39-119.4-9 8-39 116.4 144.7 29.3 9-5z"/><path fill="#e0e0e0" d="M306.2 365.31l-9 5 144.7 29.3 9-5-144.7-29.3z"/><path d="M498.9 308.71c-4.9 7.8-15.1 22.6-42.2 22.6-21.8 0-28.4-45.4-36.4-40.5 0 0-3.1-2.3-11.4-4.9s-10.7-19.1-5.5-37.4 16.1-31.3 24.4-28.9c43.4-.1 82.5 47.4 71.1 89.1z" fill="#7add99"/><path d="M455.7 280.21c-11.7-16.9-47.5-20.8-54.5-21.4-1.8 13.8 1.1 25 7.7 27 8.2 2.6 11.4 4.9 11.4 4.9 4.6-2.8 8.6 10.6 15.3 22.7a21.66 21.66 0 002.5.4c30 4.4 17.6-33.6 17.6-33.6zm43.2 28.5c-9.2 14.7-21.3 22.6-43.2 22.6 21.1-10.5 41.2-22.4 41.2-40.8 0-17-3.2-24.5-3.2-26.9 7.1 14.3 9.4 29.7 5.2 45.1z" fill="#33cc86" opacity=".35" style="isolation:isolate"/><path fill="#fff" d="M450.9 394.61l-144.7-29.3 39-119.4 138.7 69.5-33 79.2z"/><path fill="#f2f2f2" d="M368.6 272.51l-28.3 83.5-9.6-2.3 28.7-85.7 9.2 4.5zm18.5 9l-27.6 79.2-9.6-2.3 28-81.4 9.2 4.5zm18.5 9l-27 74.8-9.6-2.3 27.3-77 9.3 4.5zm18.4 8.9l-26.2 70.6-9.6-2.4 26.6-72.7 9.2 4.5z"/><path d="M428.6 281.11c0 13.9-9.3 26.5-36.9 41.8-16.6 9.2-37 5-44.6 29.9-3.3 10.7-15.5 13.2-15.5 23.5 0 4.9 4.4 8.9 9.5 10.4 0 12.2 9.5 19 20.1 16.9 2.7 3.7 7.1 6.3 13.6 6.3a23.55 23.55 0 0011-3 19.4 19.4 0 0010 2.6c23.9 0 29.2-36.3 50.3-42.9 22.4-7 43.3-23.2 56.7-45.4.1-45-54.3-50.5-74.2-40.1z" fill="#4167f2"/><path d="M379 409.41a20.07 20.07 0 01-4.2.5c-6.5 0-10.9-2.6-13.6-6.3 10.7-1.8 19.9-11.7 23.2-18.5 0 8.7-5.6 14.6-8.2 17.5-2.1 2.4-3.4 8 2.8 6.8zm23.6-1.1a20.89 20.89 0 01-6.7 1 18.48 18.48 0 01-10-2.6 38.92 38.92 0 0015.4-15.7s0 3.9-3.8 11.6c-2.2 4.5 3 5.9 5.1 5.7zm-41.5-39.9c-2.4 3.1-9 13-10 16.8a2.35 2.35 0 00-.2.8 16.32 16.32 0 01-9.7.5c-9.1-2.7-12.5-11-6.5-18.3-5.8 9 15.7 14 26.4.2z" fill="#415aff" opacity=".75" style="isolation:isolate"/><path d="M363.9 403.01l-1.3 2.2a10.1 10.1 0 01-1.3-1.6c-11 2-20.1-5.1-20.1-17 1 0 4.3 1.4 9.7-.5-2 8.1 3.1 18.7 13 16.9z" fill="#415aff" opacity=".75" style="isolation:isolate"/><path d="M352.3 382.41c3.5-7.5 11-14 11.8-22.4-.9 7.6-8.4 13.8-12.8 20.5-3.1 4.7-8.4 6.2-11.6 5.6a16.5 16.5 0 0011.2 0 18.19 18.19 0 011.4-3.7z" fill="#3354f2"/><path d="M364.1 360.01a7.57 7.57 0 00.1-1.5c0 .5-.1 1-.1 1.5z" fill="#415aff"/><g opacity=".5" style="isolation:isolate"><path d="M473.7 340.81c-16.1 11.8-27.5 19.2-40.9 35.1 3.8-4.3 8.1-7.8 13.3-9.4 22.4-7 43.3-23.2 56.7-45.4a45.32 45.32 0 00-1.5-12c.3 5-11 19.5-27.6 31.7z" fill="#3354f2" opacity=".75" style="isolation:isolate"/><path d="M432.6 376.21c.4-.4 0-.1 0 0z" fill="#415aff"/></g><path d="M372.4 329.81c-11.6 4-16.6 13.8-3.1 13.9 4.4 0 10.5-6.1 24.9-11.7 29-11.2 75.7-18.7 91-45.6-17.7-12.1-44-11.8-56.6-5.2 0 23.1-35.1 41.4-56.2 48.6z" fill="#4c77fc"/><path fill="#33cc86" d="M0 525.01l13.2 50.2 97.9 26.3 2.6-56L0 525.01z"/><path fill="#27b772" d="M113.7 545.51l-2.6 56 66.3-42.6 13.2-52.9-76.9 39.5z"/><path d="M131.2 135.91c-4 1.8-7.9 3.4-11.7 4.9-1.2-5.5-5.6-7-10.4-9.1.7-1.7 6.6-7.4 8.1-13.7 1.6-.8 3.3-1.5 5-2.3 26.7-11.9 50.3-17 52.8-11.4s-17.1 19.8-43.8 31.6z" fill="#33cc86"/><path d="M131.2 135.91c-4 1.8-7.9 3.4-11.7 4.9a9.37 9.37 0 00-3.5-5.6c-2.2-1.6-3.6-2.1-6.9-3.5.7-1.7 6.6-7.4 8.1-13.7a11.94 11.94 0 001.6-.8c1 1.7.2 8.3-1.1 13.8l5-1.5c25.3-7.9 45.8-18 52.3-25.2 2.5 5.6-17.1 19.8-43.8 31.6z" fill="#27b772" opacity=".35" style="isolation:isolate"/><path d="M121.6 108.31a2 2 0 011-2.6c24.3-10.8 36.6-10.3 37.1-10.3a2.002 2.002 0 11-.2 4c-.1 0-12-.4-35.2 9.9a2 2 0 01-2.7-1z" fill="#f9e17a"/><path d="M124.2 109.31c23.2-10.3 35.1-9.9 35.2-9.9a2.002 2.002 0 10.2-4 23.56 23.56 0 00-4 .2v.1c-.1 0 1.7 2.7-.4 3-5.3.7-15.7 2.8-31 9.6a2.38 2.38 0 01-2.8-1 1.8 1.8 0 00.2 1 1.9 1.9 0 002.6 1z" fill="#f4bc2f" opacity=".25" style="isolation:isolate"/><path d="M155.7 96.11l4.7 10.5 4.9-2.2-4.7-10.5a1.9 1.9 0 00-2.6-1l-1.2.5a2.09 2.09 0 00-1.1 2.7z" fill="#f9e17a"/><path d="M177.1 103.31c-4.6-10.2-41.1 3.8-54.4 9.6a2.79 2.79 0 00-1.6 3.3 72.46 72.46 0 003.6 10.5 60.53 60.53 0 005.4 9.7 2.86 2.86 0 003.5 1c13.3-6.1 48.1-23.8 43.5-34.1z" fill="#33cc86"/><path d="M136.2 136.21c-.9.4-1.8.8-2.5 1.2a3.33 3.33 0 01-2.2.1l-.6-.3a2.7 2.7 0 01-.7-.8c-1-1.5-1.9-3-2.8-4.6a.1.1 0 00-.1-.1c-.3-.5-.5-1-.8-1.5 2-.7 4.2-1.6 6.6-2.5 15.5-6.1 37.5-16.1 43.6-25.2a2.18 2.18 0 01.5.8c4.2 9.6-26.4 26-41 32.9z" fill="#27b772" opacity=".35" style="isolation:isolate"/><path d="M133.6 137.41a2.71 2.71 0 01-3.5-1 75.61 75.61 0 01-5.4-9.7 52.18 52.18 0 01-3.6-10.5 3 3 0 011.6-3.3c1-.4 2.2-.9 3.5-1.5q2.25 6.15 5.1 12.3c1.8 4.1 3.8 8.1 5.8 12-1.3.7-2.4 1.2-3.5 1.7z" fill="#f9e17a"/><path d="M137 135.81c-5.6 2.6-5.6 3.5-9.8-4-.1-.1-.8-1.5-.9-1.6 2-.7 4.2-1.6 6.6-2.5 1.4 2.8 2.8 5.4 4.1 8.1z" fill="#f4bc2f" opacity=".25" style="isolation:isolate"/></svg>
              </div>
        <div class="hostname-footer"><?php echo getIdentifier(); ?></div>
            <?php else : ?>
              <h1 class="is-size-3 has-text-centered has-text-weight-bold has-text-danger mb-3">We are still working! 😢</h1>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                If you are seeing this page, it is because <strong>your website migration is still in progress!</strong>
              </p>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                Our migrations on average take between 1 to 4 hours. However depending on the size of your site, issues with your previous host, inaccurate login details, a particularly large migration queue or slow-to-update DNS, rarely migrations can take considerably longer.
              </p>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                Your site will not go down or be interrupted, however it is recommended that you <strong>stop making ALL changes to your site during this time</strong>. If changes are necessary or cannot be avoided, please reach out to our team so we can be sure to grab any changes you may have made.
              </p>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                Feel free to reach out to a team member at any time with any questions you may have, we are always standing by to help. Otherwise please stay tuned for an update from an expert member of our migration team shortly!
              </p>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                DNS is rarely an issue, but in certain cases can take up to 24 hours to fully adjust around the world. <strong>Please refresh this page</strong>, it will let you know once your migration has completed and DNS has been fully propagated!
              </p>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                <strong>If you are a managed WPO customer, you might want to use this time to get to know your WPO Portal!</strong>
              </p>
              <p class="has-text-weight-normal has-text-centered content-text px-5">
                Learn about our latest feature updates and dig into how to submit a migration request, learn about email, backups, one-click staging, redirects, WP cloning, site analytics, DNS management, new WP site requests and much much more...
              </p>
              <div class="content-link has-text-centered mb-5">
                <a href="https://blog.bigscoots.com/wordpress-optimized-portal-wpo/" class="button is-link" target="_blank" rel="internal">Learn More</a>
              </div>
              <div class="bigscoots-mascot">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 558 768.8" class="center-item"><path fill="#FFCE43" d="M446.8 54c.4 2.8.9 5.7 1.7 8.6.8 2.9 1.7 5.8 2.8 8.5l9.4.5c2.3 5.3 5.4 10.3 8.9 14.7l-4 8.5c2 2.3 4 4.4 6.3 6.5 2.2 2 4.5 3.9 6.8 5.5l8.8-5.4c3.2 1.7 6.5 2.9 9.8 3.7l3.5 11.3c2.4.4 4.8.6 7.2.6s4.7-.3 6.8-.8l11.2-6.8-2.2-9.9 7.8 6.6 11.2-6.8c1.4-1.7 2.7-3.6 3.9-5.7s2.1-4.3 2.9-6.6l-8.4-8.4c.8-3.1 1.3-6.6 1.3-10.2l8.9-5.4c-.4-2.8-.9-5.6-1.7-8.6-.8-2.9-1.7-5.8-2.8-8.5l-9.4-.5c-2.3-5.3-5.4-10.3-8.9-14.7l4-8.5c-2-2.3-4-4.4-6.3-6.5-2.2-2-4.5-3.9-6.8-5.5l-8.8 5.4c-3.2-1.7-6.5-2.9-9.8-3.7L497.4.6c-2.4-.4-4.8-.6-7.2-.6s-4.7.3-6.8.8l-11.2 6.8 2.1 9.9-7.8-6.6-11.2 6.8c-1.4 1.7-2.7 3.6-3.9 5.6-1.1 2.1-2.1 4.3-2.9 6.6l8.4 8.4c-.8 3.1-1.3 6.6-1.3 10.2l-8.8 5.5zm39.8-16.1h.6c10.9-.1 22.6 10.5 26 23.9 1.8 6.8 1.1 13.1-1.5 17.5h-.6c-10.9.1-22.5-10.5-26-23.8-1.8-7-1.1-13.2 1.5-17.6z"/><path fill="#415AFF" d="M491.4 33l-11.2 6.8c-6.1 3.7-8.9 12.4-6.3 22.4 3.5 13.3 15.1 24 26 23.8l11.2-6.8c-10.9.1-22.5-10.5-26-23.8-2.6-10 .1-18.7 6.3-22.4z"/><path fill="#F9E17A" d="M448.5 62.5c-.8-2.9-1.3-5.8-1.7-8.6l9.1-.7c-.5-5.4-.1-10.4 1.1-14.9l-8.4-8.4c.8-2.3 1.7-4.5 2.9-6.6 1.1-2.1 2.4-4 3.9-5.6l8.9 7.5c2.9-2.9 6.4-5.1 10.5-6.3l-2.5-11.3c2.2-.5 4.4-.7 6.8-.8 2.4 0 4.8.2 7.2.6l3.5 11.3c4.7 1.1 9.3 3.1 13.7 6l4.9-7.7c2.3 1.6 4.6 3.5 6.8 5.5s4.3 4.2 6.3 6.5l-4 8.5c3.5 4.4 6.6 9.4 8.9 14.7l9.4.5c1.1 2.8 2 5.6 2.8 8.5.8 2.9 1.3 5.8 1.7 8.6l-9.1.7c.5 5.4.1 10.4-1.1 14.9l8.4 8.4c-.8 2.3-1.7 4.5-2.9 6.6-1.1 2.1-2.4 4-3.9 5.7l-8.9-7.5c-2.9 2.9-6.4 5.1-10.5 6.3l2.5 11.3c-2.2.5-4.5.7-6.8.8-2.4 0-4.8-.2-7.2-.6l-3.5-11.3c-4.7-1.1-9.3-3.1-13.7-6l-4.9 7.7c-2.3-1.6-4.6-3.5-6.8-5.5s-4.3-4.2-6.3-6.5l4-8.5c-3.5-4.4-6.6-9.4-8.9-14.7l-9.4-.5c-1.1-2.8-2-5.7-2.8-8.6zM499.9 86c10.9-.1 16.9-11 13.4-24.4s-15.1-24-26-23.9-16.9 11.1-13.4 24.4c3.4 13.4 15.1 24.1 26 23.9"/><path fill="#415AFF" d="M495.3 77.2l5.6-3.2c-1.1.6-2.4 1-3.9 1l-5.6 3.2c1.5 0 2.8-.4 3.9-1z"/><path fill="#415AFF" d="M487.1 49.4l-5.6 3.2c-3.5 2-5.2 6.7-3.9 12.3 1.7 7.3 8 13.3 13.9 13.3l5.6-3.2c-6 0-12.2-5.9-13.9-13.3-1.3-5.6.4-10.3 3.9-12.3zm-5.8-10.3l-5.6 3.2c2-1.2 4.4-1.8 7.1-1.8l5.6-3.2c-2.7 0-5.1.7-7.1 1.8z"/><path fill="#415AFF" d="M501.1 87.4l5.6-3.2c6.4-3.6 9.5-12.4 7.1-22.5-3.1-13.5-14.5-24.4-25.5-24.4l-5.6 3.2c11 0 22.4 10.9 25.5 24.3 2.4 10.3-.7 19-7.1 22.6z"/><path fill="#597CFF" d="M508.2 64.9c3.1 13.4-3.2 24.3-14.1 24.3-11 0-22.4-10.9-25.5-24.3s3.2-24.3 14.1-24.3c10.9-.1 22.4 10.8 25.5 24.3zm-16.7 13.3c6 0 9.4-5.9 7.7-13.3-1.7-7.3-8-13.3-13.9-13.3-6 0-9.4 5.9-7.7 13.3 1.7 7.3 7.9 13.3 13.9 13.3"/><radialGradient id="a" cx="295.082" cy="509.086" r="364.067" gradientTransform="translate(0 194.6)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#6f2415"/><stop offset="1" stop-color="#6f2415" stop-opacity="0"/></radialGradient><path opacity=".1" fill="url(#a)" d="M170.8 672.4c63.3.3 162.5 28.5 186.9 76.4 0 0 11.4 19.4 19.9 19.9s25.3-56 64.4-60.5 73.8-48.7 20-69.5c-80.2-31-119.2 33.5-171.3 33.5s-45.3-26.5-45.3-26.5l-74.6 26.7z"/><path fill="#F9E17A" d="M520.3 346.4c3.2-6.6 5.1-14.5 5.1-24 0-21.4-9.6-38-9.6-60.2 0-26.5 18.2-64.5 42.2-64.5 0 0-16-22-58.5-22-35 0-51 6.7-100 6.7-16.5 0-17.8-20.5-17.8-20.5s-55.9 3.9-55.9 46.3 27.5 77.5 8.8 103c0 0 54.1 57.8 161.7 59.7.2 0 20.1-13 24-24.5z"/><path fill="#33CC86" d="M232.6 230.8c-43.7 21.5-98.9 117.3-98.9 199.6 0 1.3.1 2.6.2 4 5.9 55.7 78.5 106.5 172.1 117.1 96 10.9 180-24.3 195.2-79.7 3.5-14.3 5.4-29.2 5.4-44.6 0-27.7-10.6-59.1-21.7-92.4-5.2-15.4-13.5-45.6-26.9-54.5l-225.4-49.5z"/><path opacity=".35" fill="#27B772" d="M294.5 324.8c-2.8-13-15.4-22.5-25.6-29.8 3.4-.8 6.4-2.6 8.4-5.6 4.5-6.7 2.1-16.2-5.5-21.4-.6-.4-1.2-.8-1.9-1.1 3.5 5.5 3.8 12.2.3 17.4-2 2.9-4.9 4.8-8.1 5.8-4-3-6.8-5.5-7.3-7.8 0 0-11.5 18-4.1 52.4 7.4 34.3 31.7 50.5 50.2 46.5-9.9-16.4-.3-27.8-6.4-56.4z"/><path opacity=".35" fill="#7ADD99" d="M298.1 243.1l-65.5-12.3c-43.7 21.5-98.9 117.3-98.9 199.6 0 1.3.1 2.6.2 4 4.3 40.7 44.2 78.8 102 100.5-8-11.6-25.4-45.8-25.4-124.7 0-76.5 70.7-144.5 70.7-144.5l16.9-22.6z"/><path fill="#7ADD99" d="M213.7 244c-22.5 20.1-45.6 55.2-61.1 95-6.6-9.8-10.9-21.7-10.9-34.4 0-34 27.5-61.5 61.5-61.5 3.6 0 7.1.3 10.5.9z"/><path fill="#7ADD99" d="M213.7 244c-22.5 20.1-45.6 55.2-61.1 95-6.6-9.8-10.9-21.7-10.9-34.4 0-34 27.5-61.5 61.5-61.5 3.6 0 7.1.3 10.5.9z"/><path opacity=".35" fill="#33CC86" d="M171.5 299c-3.3-5.2-5.5-11.2-6.3-17.7-1.3-11 1.6-21.5 7.3-30-18.4 10.6-30.9 30.5-30.9 53.3 0 12.7 4.2 24.6 10.9 34.4 5.6-14.1 12-27.6 19-40z"/><path fill="#597CFF" d="M228.5 549c-17.1 0-21.7 9.1-21.7 16.4 0 14 26.6 66.7 26.6 80.6 0 13.8 12-.3 12-.3s2.4-22.3 2.4-34.3c0-22.3-12.2-34.8-12.2-34.8"/><path fill="#415AFF" d="M250.2 611.4c-13.8-15.6-39.1-15-39.1-15 12.1 17.5 14.7 34.1 17.7 44.4h-.7c-14.1 0-57.3 26.2-57.3 31.5 0 5 65.6-3.8 73.8-22.7.5-1.1.9-2.2 1.4-3.3 3.5-7.5 2.6-21.8 4.2-34.9z"/><path opacity=".5" fill="#415AFF" d="M228.5 549c-17.1 0-21.7 9.1-21.7 16.4 0 14 26.6 66.7 26.6 80.6 0 13.8 12-.3 12-.3s2.4-22.3 2.4-34.3c0-22.3-12.2-34.8-12.2-34.8"/><path fill="#415AFF" d="M484.1 502.5c-5.2 11.2-11.4 21.8-18.7 31.6-35 27.5-94.3 41.8-159.2 34.4-73.6-8.4-133.8-42.2-157.2-83.5-2.3-6.8-4.2-13.9-5.7-21 23.4 43.2 85.9 78.8 162.5 87.5 76.9 8.7 145.9-12 178.3-49z"/><path fill="#597CFF" d="M465.5 534.1c-32.9 44.6-85.8 73.6-145.4 73.6-79.5 0-146.9-51.3-171-122.7 23.4 41.3 83.6 75.2 157.2 83.6 64.9 7.3 124.1-7 159.2-34.5z"/><path opacity=".35" fill="#27B772" d="M358.6 364.5c0-38.2-28.8-62.3-48.9-74.4-11.5-6.3-19-9.5-19-9.5l14.7-39.8 9.1 7.9L458 280.2c13.4 8.9 21.7 39.2 26.9 54.5 11.2 33.3 21.8 64.7 21.8 92.4 0 7.7-.5 15.3-1.4 22.7s-2.2 14.8-4 21.9c-15.3 55.4-99.2 90.6-195.2 79.7-5.1-.6-10.2-1.3-15.2-2.1 74.9-4 94.4-49.7 94.4-104-.2-51.9-26.7-80.8-26.7-80.8z"/><path fill="#27B772" d="M505.2 449.9c.9-7.4 1.4-15 1.4-22.7 0-16.9-3.9-35.2-9.6-54.5l-57.9-1.8v4.2c8.2 5.9 51.2 57.5 48.5 66.8-5.5-2.4-11.3-4.2-17.3-5.5-65.3-13.6-49.5 31-49.5 31 32.6 19.3 49 47 33.2 59.3 24.2-14 41.2-32.9 47.3-54.9.9-3.8 1.7-7.7 2.4-11.6.1-.8.3-1.6.4-2.5.1-.5.2-1 .2-1.5l.9-6.3z"/><path opacity=".5" fill="#158C51" d="M420.7 467.4c6 3.5 11.7-7.4 16.5-3.4l6.9 11.6s30.2 6.1 43-12.4c8.3-12-4.1-21.2-13.9-26.2-1-.2-1.9-.5-2.9-.7-65.4-13.5-49.6 31.1-49.6 31.1z"/><path fill="none" stroke="#EF3FB9" stroke-miterlimit="10" d="M283.4 277.1c0-.1-.1-.1 0 0"/><path opacity=".5" fill="#415AFF" d="M465.5 534.1c-32.9 44.6-85.8 73.6-145.4 73.6-7.4 0-14.8-.5-22-1.3 23.5-7.9 32.9-16.8 35.3-35.9 54 1.1 102-12.7 132.1-36.4z"/><path fill="#597CFF" d="M404.6 580.4c0 25.7-19.7 48.3-25 70.3-5.6 22.9-2.6 41.5-15.9 41.5-13 0-11.9-20.4-18.4-41.3s-20.5-42.2-20.5-50.4"/><path opacity=".5" fill="#415AFF" d="M404.6 580.4c0 25.7-19.7 48.3-25 70.3-5.6 22.9-2.6 41.5-15.9 41.5-13 0-11.9-20.4-18.4-41.3s-20.5-42.2-20.5-50.4"/><path fill="#415AFF" d="M374.2 686.3c7.3-11.7 5.3-38.1 21.6-54.6 0 0-33-8.2-64 2 14.9 21.3 14.6 43.7 21.7 53.5-4 8.7-3.5 24.7-1.2 41.8 3.3 24.8 16.7 40.8 25.2 39.7s13-22.8 10.1-44.3c-1.8-14.4-6.4-29.8-13.4-38.1z"/><path fill="#597CFF" d="M359.8 600.5c-2.2 13.1-6.7 23.5-14.1 22.3-7.5-1.3-11.7-12.9-9.5-26s10.1-22.7 17.5-21.5 8.4 12.1 6.1 25.2z"/><path opacity=".5" fill="#597CFF" d="M369.3 716.9c1.3 14.5 2.5 25.7-2.9 26.2s-10.8-10.9-12.1-25.3c-1.3-14.5 2.1-26.6 7.5-27 5.4-.6 6.2 11.6 7.5 26.1z"/><path opacity=".35" fill="#27B772" d="M256.1 282.9c-27.9 2.7-47.7 19.5-44.8 49.9 1.1 11.8-1.2 28.1-6.9 28.6 14.9-1.4 25.5-15.8 35.9-37.1 5.9-12 3.6-24.5 15.8-41.4z"/><path fill="#FFCE43" d="M255 283c-.2 0-.4 0-.6.1.2-.5.4-.7.4-.7 0 .2.1.4.2.6zm1.1-.1c-.2.3-.5.7-.7 1-.2-.3-.3-.6-.4-.9.3-.1.7-.1 1.1-.1z"/><path opacity=".5" fill="#415AFF" d="M252.4 288.6c-.7-.4-1.1-2.1-1.9-2.3-2.3 5-5.6 14.9-6.1 27.1 2.4-8 4.7-17.3 8-24.8z"/><path fill="#7ADD99" d="M531.2 382c0 5.5-.7 10.8-2.1 15.9-4.9 7.8-15.1 22.6-42.2 22.6-21.8 0-28.4-45.4-36.4-40.5 0 0-3.1-2.3-11.4-4.9-8.2-2.6-10.7-19.1-5.5-37.4s16.1-31.3 24.4-28.9c40.4-.1 73.2 39.5 73.2 73.2z"/><path opacity=".35" fill="#33CC86" d="M485.9 369.4c-11.7-16.9-47.5-20.8-54.5-21.4-1.8 13.8 1.1 25 7.7 27 8.2 2.6 11.4 4.9 11.4 4.9 4.6-2.8 8.6 10.6 15.3 22.7.8.2 1.7.3 2.5.4 30 4.4 17.6-33.6 17.6-33.6zm45.3 12.6c0 5.5-.7 10.8-2.1 15.9-4.9 7.8-15.1 22.6-42.2 22.6h-1c21.1-10.5 41.2-22.4 41.2-40.8 0-17-3.2-24.5-3.2-26.9 4.7 9.5 7.3 19.6 7.3 29.2z"/><path fill="#4D75F4" d="M533.1 410.3c-12 19.5-36.8 28.8-55.5 35.1-.5 16.9-9 30.3-31.7 30.3-23.4 0-42.4-19-42.4-42.3 0-42.1 60.9-50.9 60.9-68 49.1-.1 68.7 32.1 68.7 44.9z"/><path opacity=".5" fill="#415AFF" d="M477.6 445.4c-.4 12.9-5.5 23.8-18.1 28.2 3.3-1.6 5.5-5 5.5-9 0-5.5-4.5-10-10-10h-.1c3-3 11.8-5.6 22.7-9.2z"/><path fill="#415AFF" d="M466.8 459c.9-3.5-.9-7.1-4.1-8.5-1.1.4-2.2.9-3.1 1.3 4 0 7.2 3.2 7.2 7.2z"/><path fill="#597CFF" d="M522.2 388c-11.6 19.1-46.6 30.2-68.1 43.3-11.6 7.1-26.2 7.2-37.8 0l-.9-.6c-7.2-4.4-9.2-14-4.3-20.9 16.3-22.9 53.4-31.2 53.4-44.5 28.6 0 47.2 11 57.7 22.7z"/><path fill="#27B772" d="M455.4 302.8c-5.2.6-9.7 3.9-12.6 6.6-1.3 1.2-2.9 1.9-4.6 2.2-20.9 3.4-52.4.7-85.2-8.2-16.8-4.5-38.7-12-54.3-19-3.4-1.7-5.8-2.8-7.1-3.4-.3-.2-.6-.4-1-.6l4.5-12.1c1.2.2 2.5.3 3.8.3 0 0 22.7 15.8 65.6 15.8 32.4 0 54.1-3.7 68.7-9.6l24.6 5.4c4 5.3 5.7 10 5.6 14.1 0 4.4-3.6 7.9-8 8.5z"/><path fill="#FFD2CF" d="M238 207.7c46.2-47.7 98.5-77.2 132.5-77.2 44.9 0 81.2 36.4 81.2 81.2 0 19.3 2.8 38.4 7.1 57.1.6 3.4.5 6.7-.4 9.9-7.2 26.2-63.7 33.7-126.2 16.8-62.5-16.9-107.3-51.8-100.1-78 1.1-3.6 3.1-6.9 5.9-9.8z"/><path fill="#FFD2CF" d="M278.4 169c9.6-7.2 19.2-13.6 28.5-19l4.2 5-27.8 18.7-4.9-4.7z"/><path fill="#E8ACAC" d="M336.3 239.8s-.3.2-.9.5c-.1.1-.3.1-.5.2s-.4.1-.6.2c-.4.1-.9.2-1.5.2h-.8c-.3 0-.6-.1-.9-.1-.6-.1-1.2-.3-1.9-.5-.6-.3-1.3-.5-1.9-1-.6-.4-1.2-.9-1.7-1.4s-1-1.1-1.4-1.7-.7-1.2-1-1.9c-.2-.7-.4-1.3-.5-1.9 0-.3-.1-.6-.1-.9v-.8c0-.5.1-1 .2-1.4s.2-.8.4-1.1c.3-.6.5-.9.5-.9s.2.3.5.8c.1.2.3.5.6.8.2.3.4.7.7 1 .2.4.5.7.8 1.1.2.4.6.7.9 1.1s.6.7 1 1.1c.3.4.7.7 1 1.1.4.3.7.7 1.1 1 .3.3.7.6 1.1 1 .4.3.7.6 1.1.9s.7.6 1.1.8c.4.3.7.5 1 .7l.9.6c.5.2.8.5.8.5z"/><path fill="#415AFF" d="M323.1 90.9c-.6 4.3-.8 8.7-.5 13.1 2 31.4 27.8 56.6 59.3 57.9 12.5.5 24.2-2.7 34.1-8.5-1 3.7-1.6 7.5-1.6 11.5 0 23.6 19.1 42.8 42.7 42.8v-33.4c0-18.4-14.9-33.3-33.3-33.3 0-22.5-18.2-40.7-40.7-40.7-16.5 0-21.8 4.5-31.5 4.5-12.6 0-20.9-9.1-24.9-14.8-1-1.4-3.4-.9-3.6.9z"/><path opacity=".75" fill="#35071E" d="M349 175.2s10.1-.5 13.7 7c0 0-5.9.6-10 5.7 0 0-4-4.6-3.7-12.7zm-43.6-15s8.4-.4 11.4 5.8c0 0-4.9.5-8.4 4.8.1 0-3.3-3.9-3-10.6z"/><path opacity=".5" fill="#A01755" d="M351.3 216.1c-.5-.6-1.1-1-1.9-1.1-.7-.1-1.5.2-2.1.6-9.3 8-23.4 7.5-32.1-1.2-.4-.4-1-.4-1.4 0s-.4 1 0 1.4c5.4 5.9 13.1 9 20.9 9 5.6 0 11.2-1.6 15.9-4.9.6-.4 1-1.1 1.1-1.8.2-.7 0-1.4-.4-2z"/><path opacity=".35" fill="#EFBBBB" d="M458.5 278.7c-7.2 26.2-63.7 33.7-126.2 16.8-18.1-4.9-34.7-11.3-49-18.5-21.4-12.7-16.6-34.8-16.6-34.8 0 12.9 10.4 23.3 23.3 23.3 0 0 21 14.7 60.7 14.7 81 0 90.1-25 90.1-48.4l11.5-4.5c1 14 3.4 27.8 6.5 41.5.7 3.4.6 6.7-.3 9.9z"/><path fill="#E8ACAC" d="M290.1 265.5s-.6.2-1.6.4c-.3.1-.5.1-.8.2-.3 0-.7.1-1 .1-.8.1-1.6.1-2.5 0-.5 0-.9-.1-1.4-.1l-1.5-.3c-1.1-.2-2.1-.7-3.2-1.1-1-.5-2.2-1.1-3.2-1.8-1.1-.7-2-1.5-2.9-2.4-.8-.9-1.7-1.9-2.4-2.9-.8-1-1.3-2.1-1.8-3.2-.4-1.1-.9-2.1-1.1-3.2l-.3-1.5c0-.5-.1-1-.1-1.4-.1-.9-.1-1.8 0-2.5 0-.4 0-.7.1-1s.1-.6.2-.9c.2-1 .4-1.6.4-1.6s.2.6.6 1.5c.1.2.2.5.3.8.1.3.2.6.4.9.3.6.5 1.3.9 2.1.2.4.3.8.5 1.1.2.4.4.8.7 1.2.4.8 1 1.6 1.5 2.4.6.8 1.1 1.6 1.8 2.4.6.8 1.3 1.5 2 2.3.8.7 1.5 1.4 2.3 2 .8.7 1.6 1.2 2.4 1.8.8.5 1.6 1.1 2.4 1.5.4.2.8.4 1.2.7.4.2.8.4 1.1.5.7.4 1.4.6 2.1.9.3.1.6.3.9.4s.6.2.8.3l1.2.4z"/><path fill="#597CFF" d="M414.8 133.9c0 17.2-23.7 21.6-36.3 21.6-12.7 0-34.2-5.9-43.8-18.6-6.9-9.3-11.3-20.5-12.1-32.8-.3-4.5-.1-8.9.5-13.1.3-1.8 2.6-2.3 3.6-.9 4 5.6 12.3 14.8 24.9 14.8 9.8 0 15-4.5 31.5-4.5 4.5 0 8.8.7 12.8 2 10.7 7.3 18.9 15.3 18.9 31.5z"/><path opacity=".75" fill="#EFBBBB" d="M455.1 250.2c-2.2-11.1-14.3-18.3-14.3-18.3l11.5-4.5c.6 7.6 1.6 15.2 2.8 22.8z"/><path fill="#415AFF" d="M423.8 141c0-18-11.7-33.3-27.9-38.6 10.8 7.3 18.9 15.3 18.9 31.5 0 3.3-.9 6.1-2.4 8.5-.1-18.5-15.5-25-15.5-25 4.5 3.1 7 10.3 7.6 17.4.8 9.6-5.9 18.2-15.4 19.7-3.8.6-7.5.9-10.6.9-12.7 0-34.2-5.9-43.8-18.6 10.9 14.6 28 24.3 47.2 25.1 12.5.5 24.2-2.7 34.1-8.5-1 3.7-1.6 7.5-1.6 11.5 0 23.6 19.1 42.8 42.8 42.8v-33.4c-.1-18.4-15-33.3-33.4-33.3z"/><path fill="#597CFF" d="M433.9 155.7c5 3.7 9.7 6.1 12.2 2.8 2.2-3 .7-7.9-3.3-11.5-2.4-1.6-4.9-3-7.7-4-3-.6-5.8.1-7.2 2.1-2.5 3.2 1 6.9 6 10.6z"/><path opacity=".35" fill="#FFF" d="M305.6 180.7c-6.4-2.3-18.3-7.3-23.6-12.8-1.5-7.6-.7-16.3-.1-21.2 7.5 1.6 24.2 5.7 38.6 13.2-2.5 4.6-7.7 13-14.9 20.8zm69.3 23.1c-4.9-1.1-17.6-4.2-34.6-10.8-.6-7.4-1.6-20.9-1.6-28.6 8.7 1.6 29 6 49.1 15.8-1.9 5.1-6.2 15.6-12.9 23.6z"/><path opacity=".5" fill="#FFF" d="M365.8 171.3L339.6 184c-.2-2.6-.4-5.3-.5-8l16.3-7.9c3.2 1 6.8 2 10.4 3.2z"/><path fill="#2E4EED" d="M311.9 556.4c60.3 6.9 116.1-3.2 155.3-24.7 6.5-9.1 12.2-18.9 16.9-29.2-32.4 37-101.4 57.7-178.2 49-76.6-8.7-139.2-44.3-162.5-87.5.9 4.1 1.9 8.2 3 12.3 29.9 40 91.6 71.7 165.5 80.1z"/><path fill="#F9E17A" d="M265.9 575.2c-31.8-1.8-57.2-19.2-57.2-19.2-7.7-18.6-8.6-40.5-8.6-40.5s2.7 1.8 7.6 4.2c9 4.4 25.6 11 46.7 12.3 0 0 4.6 25.7 9.8 39.5.6 1.3 1.2 2.6 1.7 3.7z"/><path fill="#FFD442" d="M272.9 572.2l-7 3c-.5-1.1-1.1-2.4-1.7-3.9-5.3-13.8-9.8-39.5-9.8-39.5-21.1-1.3-37.7-7.8-46.7-12.3-4.9-2.4-7.6-4.2-7.6-4.2l7-3s21.7 14.5 54.3 16.5c0 .1 5.6 31.3 11.5 43.4z"/><path opacity=".2" fill="#FFF" d="M224 526.1l-19.9 15.8c-.8-3.2-1.5-6.4-2-9.3l12.5-9.9c2.8 1.2 6 2.3 9.4 3.4zm15.5 3.9l-31.2 24.8c-.5-1.4-1.1-2.8-1.5-4.2l27.4-21.8c1.6.5 3.4.9 5.3 1.2z"/><path fill="#415AFF" d="M301.8 142.7l21.7-6.3 2.1 12.5-22.7-.5zm72.9 25.5l-23.9-13 8.9-12.6 18.9 19.9z"/><path fill="#253CAF" d="M452.6 199.7c-9.2-3-44.8-13.7-57.1-17.4.6-1.7.9-2.8 1-3l.9-3.2-2.9-1.5c-23.9-12.4-48.3-17.2-56.4-18.5-1-2-2.8-3.7-5.2-4.4-2.8-.8-5.6-.2-7.8 1.5-20.4-10.9-44.7-15-45.8-15.2l-3.8-.6-.8 3.8c-.1.7-3.3 16.3-.3 29.5l.2 1 .6.8c8.2 9.7 29.4 16.5 30.3 16.8l2.4.8 1.7-1.8c11.9-12.3 19.1-26.4 19.9-28 .2-.3.5-.7.8-.8.2 0 .4 0 .4.1-.7 8.2 1.6 35.5 1.7 36.7l.2 2.5 2.3.9c23.9 9.5 40 12.5 40.6 12.6l2.2.4 1.5-1.6c6-6.6 10.4-14.7 13.2-21.1 11.5 3.4 48.5 14.5 57.6 17.5 10.8 3.6 16.8 18.7 16.1 30.8-.1 2.2 1.6 4.1 3.8 4.2h.2c2.1 0 3.9-1.6 4-3.8 1.1-17.6-8.4-34.7-21.5-39zm-148.9-19.9c-6.4-2.3-16.4-6.4-21.8-11.9-1.5-7.6-.7-16.3-.1-21.2 7.5 1.6 22.3 4.8 36.8 12.3-2.5 4.6-7.7 13-14.9 20.8zm27.1-20.4zm42.2 43.5c-4.9-1.1-15.8-3.3-32.8-9.9-.6-7.4-1.6-20.9-1.6-28.6 8.7 1.6 27.2 5.1 47.3 14.9-1.9 5.1-6.1 15.6-12.9 23.6z"/><path fill="#153196" d="M396.5 179.3l.9-3.2-2.9-1.5c-1.5 15.1-18.9 37.5-18.9 37.5l2.2.4 1.5-1.6c6-6.6 10.4-14.7 13.2-21.1l3-7.5c.6-1.7.9-2.8 1-3zm-73-13.3c-1.4 2.3-13.1 19.6-18 23.1l2.4.8 1.7-1.8c11.9-12.3 19.1-26.4 19.9-28 .2-.3.5-.7.8-.8-3.5-.6-5.4 4.4-6.8 6.7zm126.6 41.3c10.8 3.6 16.8 18.7 16.1 30.8-.1 2.2 1.6 4.1 3.8 4.2h.2c2.1 0 3.9-1.6 4-3.8 1-17.2-8.2-33.9-20.9-38.6 3.1 1.5 2.9 5.5-.7 4.3l-58.6-18-1.4 3.6c11.5 3.5 48.4 14.5 57.5 17.5zm-56.1-21l1.5-4z"/><path fill="#FFD2CF" d="M437 207.1c6.1-6.1 15.9-6.1 21.9 0 6.1 6.1 6.1 15.9 0 21.9-6.1 6.1-15.9 6.1-21.9 0"/><path fill="#E8ACAC" d="M447.7 211c-2 0-3.7.7-5.1 2 .9-.3 1.8-.5 2.7-.5 4.5 0 8.2 3.7 8.2 8.2 0 1.3-.3 2.5-.8 3.6 1.6-1.4 2.6-3.4 2.6-5.7-.1-4.2-3.4-7.6-7.6-7.6z"/><path fill="#EFBBBB" d="M330.3 171.8l-17.8-1 9.3 18.3"/><path fill="#FFCE43" d="M296.8 395c-12.4 10.7-27.7 21.7-41.8 31.3-7.8 5.3-15.3 10.1-21.7 14.2-20.7-4.2-32.3-13.8-36.5-18-2.5-6.2-4.6-12.2-6.4-18.2-14.7-48.8-7.2-88.6-7.2-88.6 16-20 44.2-31.2 44.2-31.2s22.2 23.3 59.2 30l20.2 48.4-25.1 10.5c-3.9 4.3 6.1 11.4 15.1 21.6z"/><path opacity=".35" fill="none" stroke="#7ADD99" stroke-miterlimit="10" d="M181.9 326.9c-.1-.1-.1-.2-.1-.2l.1-.1"/><path fill="none" stroke="#7ADD99" stroke-miterlimit="10" d="M216.1 289.9c-.2-.3-.2-.4-.2-.4m60.6 122h0m-87-7.8l-.2-.2m7.4 18.9c-.1-.1-.2-.2-.4-.3m-.1-.2c-.1-.2-.3-.3-.4-.4m-.8-.9c-.1-.1-.1-.1 0 0"/><path fill="#F9E17A" d="M222.2 297.4c7.8 8.3 23.4 21.1 50.2 29.2 4.9 11 9 22 12.2 30.8-1 .9-2 1.9-3 2.8-6.2-1.2-13.3-2.8-21-5.2-20.4-6.3-44.7-17.8-66.5-38.3 7.8-6.1 18.3-13.8 28.1-19.3zm3.1 124.4c-6.4-1.9-13.4-4.5-20.4-8.2-5.5-16.9-12.8-44.5-13.6-73.7 10 10.9 29.4 26.6 65.6 40.3 4.1 1.5 8.3 3 12.8 4.5l1.5 5.4c-12.1 8.9-28.8 20.7-45.9 31.7zm-30.4-1.3s15.3 18 41.4 20.5l.3-.1c6.2-4.4 13.2-9.4 20.4-14.7 6.4-4.7 13.1-9.7 19.6-14.8 7.1-5.5 14.1-11.1 20.3-16.5-.3-1-.6-1.9-.8-2.7-1.7-5.8-2.8-10.1-5.3-16.5l16.1-12.8-20.2-48.4c-8.6-1.6-16.4-4-23.3-6.9-12.4-5.1-22-11.5-28.1-16.3-5.1-4-7.8-6.8-7.8-6.8s-4.6 1.8-11.2 5.4c-6.3 3.4-14.5 8.3-22 14.7-2.2 1.8-4.3 3.8-6.3 5.9-1.6 1.7-3.2 3.4-4.6 5.2 0 0-.7 4-1.2 10.9v.3c-1.1 15.2-1.4 44.1 7.6 76.8 1.2 5.5 3 11.1 5.1 16.8"/><path fill="#F4BC2F" d="M197.1 317.7c7.5-5.8 17.4-13.1 26.8-18.6-.6-.6-1.2-1.2-1.7-1.8-9.8 5.6-20.3 13.2-28.1 19.3 21.2 19.8 44.6 31.2 64.5 37.6-19.3-6.5-41.4-17.6-61.5-36.5zm10.8 97c-5.4-16.5-12.4-43.1-13.5-71.5-1.1-1.1-2.1-2.2-3.1-3.2.8 29.2 8 56.8 13.6 73.7 5.9 3.1 11.7 5.4 17.2 7.2-4.6-1.7-9.4-3.7-14.2-6.2z"/><path opacity=".25" fill="#D19E20" d="M296.4 338.2l-9.9-23.7c-15.1-2.8-27.8-8.3-37.5-13.9-1.2 8.5-1.4 19.9 1.6 34.1 6.5 30.5 26.5 46.7 43.8 47.1-1.1-1.8-2.4-3.8-3.9-6.1l6.2-4.9c-1.7-9.2.8-18.4-.3-32.6z"/><path fill="#597CFF" d="M285 373.2c-7.3-15.3 2.1-24.4-.8-50.1-2.3-19.8-30.3-33.1-31.2-40.6 0 0-11.6 14.8-8.1 45.6s23.5 47 40.1 45.1z"/><path opacity=".5" fill="#415AFF" d="M285 373.2c-7.3-15.3 2.1-24.4-.8-50.1-1-8.8-7.1-16.3-13.6-22.6 3.8 4.8 12.8 17 8.7 35.6-3.2 14.5-2.8 29.8-2.8 29.8-7.9-.9-18.8-8.1-26.3-18 7.9 17.8 22.3 26.8 34.8 25.3zm-20.4-78.1c-6-5.2-11.2-9.4-11.5-12.5 0 0-1.7 2.2-3.6 6.7-.1 0 2.8 7.6 15.1 5.8z"/><path opacity=".25" fill="#D19E20" d="M240.4 324.2c3.7-7.6 4.1-15.4 7.1-24.5-6.3-3.8-11.2-7.4-14.6-10.3-14.8 7.9-23.5 22.4-21.5 43.3 1.1 11.8-1.2 28.1-6.9 28.6 14.8-1.4 25.4-15.7 35.9-37.1z"/><path fill="#597CFF" d="M256.1 282.9c-28 0-49.4 14.8-49.4 45.3 0 11.8-4 27.8-9.6 27.8 15 0 26.9-13.3 39.3-33.5 7-11.3 5.9-23.9 19.7-39.6z"/><path opacity=".5" fill="#415AFF" d="M243.7 283.9c-7.7 5.2-18 15.3-20.8 33.1 0 0 2.9-13.4 17.3-24-2.2 9.3-12.6 47.9-37.8 62.4.5-.1 1.1-.3 1.7-.4 11.9-3.4 21.9-15.5 32.4-32.5 7-11.3 5.9-23.9 19.7-39.7-4.4.1-8.6.4-12.5 1.1z"/><ellipse transform="rotate(-55.813 260.602 276.086)" fill="#F9E17A" cx="260.6" cy="276.1" rx="14.6" ry="16.5"/><path fill="#FFCE43" d="M274.2 285.4c-4.5 6.7-14.3 7.9-21.9 2.8-.8-.6-1.6-1.2-2.4-1.9 6.7 1.6 13.5-.5 17.2-6 3.5-5.2 3.2-11.9-.3-17.4.6.3 1.3.7 1.9 1.1 7.6 5.1 10.1 14.7 5.5 21.4z"/><path fill="#F9E17A" d="M12 279.9l-6.1-3.2c-5-2.7-7.2-9.5-5.1-17.2l6.1 3.2c-2.1 7.6.2 14.5 5.1 17.2z"/><path fill="#FFCE43" d="M63.8 157.2L34.2 265.1c-2.6 9.5-10.8 16.6-18.4 16-7.5-.7-11.6-8.9-9-18.3l29.6-107.9 27.4 2.3z"/><path fill="#F9E17A" d="M6.9 262.6l-6-3.2 29.5-107.8 6.1 3.2z"/><path fill="#F9E17A" d="M36.5 154.8l-6.1-3.2 27.4 2.3 6 3.3z"/><path fill="#597CFF" d="M73.7 127.9l-8.1-4.3 9.5-34.8 8.1 4.3zM42 125.2l-8-4.3 31.6 2.7 8.1 4.3z"/><path fill="#597CFF" d="M31.1 164.5l-6.1-3.2c-10.7-5.7-18.1-18.2-13.2-36.1 4.9-18 17.5-32.8 31.7-39.1l8.1 4.3c-14.1 6.3-26.7 21.1-31.7 39.1-4.9 17.9.4 29.3 11.2 35z"/><path fill="#415AFF" d="M83.2 93.1c10.1 8.4 14.2 24.7 9.2 42.7-6.9 25.1-26.1 34.6-46.1 32.9-26.1-2.3-32.6-16.4-26.4-39.2 4.9-18 17.5-32.8 31.7-39.1l-9.5 34.8 31.6 2.8 9.5-34.9z"/><path fill="#EAEAEA" d="M521.3 491.6l-4.6 9.9-16.5-13.7-.5-7.5-31.1-31.9c2.4-.8 5-1.7 7.8-2.6l27.5 26.3 5.8-1 11.6 20.5z"/><path fill="#F7F9F7" d="M516.7 501.5l-18.5-7.2-1.1-6.3-34.9-37.4c1.9-.8 4.1-1.5 6.5-2.3l31 32 .5 7.5 16.5 13.7z"/><path fill="#F9E17A" d="M431.7 391.4c-2.7 1.8-5.4 3.6-7.9 5.6-4.8 3.7-9.2 8-12.7 12.8l-10-15.5c-1.2-1.9-1.8-4-1.8-6.2 0-1.1.2-2.2.5-3.2.1-.5.3-1 .6-1.5v-.1c.1-.2.2-.5.4-.7 0 0 0-.1.1-.1.1-.2.2-.3.3-.5l.6-.9.6-.6.5-.5.2-.2c.2-.2.4-.4.6-.5 4.3-3.5 10.5-3.4 14.7.2l13.3 11.9z"/><path fill="#FFCE43" d="M431.7 391.4c-2.7 1.8-5.4 3.6-7.9 5.6L412 384.7c-2.1-2.2-5.5-2.9-8.3-1.5l-3.9 1.9c.1-.5.3-1 .6-1.5v-.1c.1-.2.2-.5.4-.7 0 0 0-.1.1-.1.1-.2.2-.3.3-.5l.6-.9.6-.6.5-.5.2-.2c.2-.2.4-.4.6-.5 4.3-3.5 10.5-3.4 14.7.2l13.3 11.7z"/></svg>
              </div>
        <div class="hostname-footer"><?php echo getIdentifier(); ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</body>
</html>