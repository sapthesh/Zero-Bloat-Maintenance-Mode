<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$zbmm_launch_date = get_option('zbmm_countdown_date'); 
$zbmm_custom_css  = get_option('zbmm_custom_css');
$zbmm_logo_url    = get_option('zbmm_logo_url');
$zbmm_message     = get_option('zbmm_message', 'We are currently performing scheduled maintenance. We’ll be back shortly!');
$zbmm_site_name   = get_bloginfo('name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $zbmm_site_name ); ?> - Under Maintenance</title>
    
    <meta property="og:title" content="<?php echo esc_attr( $zbmm_site_name . ' - Under Maintenance' ); ?>" />
    <meta property="og:description" content="<?php echo esc_attr( wp_strip_all_tags( $zbmm_message ) ); ?>" />
    <meta property="og:type" content="website" />
    <?php if ( $zbmm_logo_url ) : ?>
    <meta property="og:image" content="<?php echo esc_url( $zbmm_logo_url ); ?>" />
    <?php endif; ?>

    <style>
        body { text-align: center; padding: 100px 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #333; background: #f4f7f6; margin: 0; }
        .container { max-width: 550px; margin: 0 auto; background: #fff; padding: 60px 40px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
        .branding-logo { max-width: 180px; max-height: 120px; height: auto; margin: 0 auto 20px auto; display: block; }
        h1.emoji-fallback { font-size: 50px; margin: 0 0 20px; }
        p { font-size: 18px; line-height: 1.6; color: #666; margin-bottom: 30px; }
        #timer { display: flex; justify-content: center; gap: 10px; }
        .time-block { background: #1a1a1a; color: #fff; padding: 15px; border-radius: 8px; min-width: 70px; }
        .time-block span { display: block; font-size: 24px; font-weight: bold; }
        .time-block .label { font-size: 10px; text-transform: uppercase; opacity: 0.6; margin-top: 5px; letter-spacing: 1px; }
        
        <?php 
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo wp_strip_all_tags( $zbmm_custom_css ); 
        ?>
    </style>
</head>
<body>
    <div class="container">
        
        <?php if ( $zbmm_logo_url ) : ?>
            <img src="<?php echo esc_url( $zbmm_logo_url ); ?>" alt="<?php echo esc_attr( $zbmm_site_name ); ?> Logo" class="branding-logo" />
        <?php else : ?>
            <h1 class="emoji-fallback">🚧</h1>
        <?php endif; ?>

        <p><?php echo esc_html( $zbmm_message ); ?></p>

        <?php if ( $zbmm_launch_date ) : ?>
        <div id="timer" data-date="<?php echo esc_attr($zbmm_launch_date); ?>">
            <div class="time-block"><span id="days">00</span><div class="label">Days</div></div>
            <div class="time-block"><span id="hours">00</span><div class="label">Hours</div></div>
            <div class="time-block"><span id="minutes">00</span><div class="label">Mins</div></div>
            <div class="time-block"><span id="seconds">00</span><div class="label">Secs</div></div>
        </div>

        <script>
            const target = new Date(document.getElementById('timer').dataset.date).getTime();
            const update = () => {
                const now = new Date().getTime();
                const diff = target - now;
                if (diff < 0) { document.getElementById('timer').style.display = 'none'; return; }
                
                document.getElementById('days').innerText = Math.floor(diff / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
                document.getElementById('hours').innerText = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0');
                document.getElementById('minutes').innerText = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
                document.getElementById('seconds').innerText = Math.floor((diff % (1000 * 60)) / 1000).toString().padStart(2, '0');
            };
            setInterval(update, 1000); update();
        </script>
        <?php endif; ?>
    </div>
</body>
</html>