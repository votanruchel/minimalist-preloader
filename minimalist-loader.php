<?php
/**
 * Plugin Name: Minimalist Loader
 * Plugin URI: https://votan.dev
 * Description: Preloader minimalista que aguarda o carregamento do Google Ad Manager.
 * Author: Votan Ruchel
 * Version: 1.0.0
 * Author URI: https://votan.dev
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

// CONSTANTES
define('MINIMALIST_LOADER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MINIMALIST_LOADER_PLUGIN_DIR', plugin_dir_path(__FILE__));

function minimalist_loader_register_settings()
{
    // Opções de cores e layout
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_spinner_color', [
        'default' => '#000000'
    ]);
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_spinner_bg_color', [
        'default' => '#ffffff'
    ]);
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_background_color', [
        'default' => 'rgba(255,255,255,0.8)'
    ]);
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_use_blur', [
        'default' => '1'
    ]);

    // Opções de tempo
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_min_time', [
        'default' => '1000'
    ]); // Em milisegundos
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_max_time', [
        'default' => '5000'
    ]);

    // Opção de evento GPT
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_gpt_event', [
        'default' => ''
    ]);

    // Nova opção para selecionar o tipo de anúncio
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_ad_type', [
        'default' => 'admanager'
    ]);

    // Opção para o slot ID do AdSense
    register_setting('minimalist_loader_settings_group', 'minimalist_loader_adsense_slot', [
        'default' => ''
    ]);
}
add_action('admin_init', 'minimalist_loader_register_settings');

function minimalist_loader_add_admin_menu()
{
    add_options_page(
        'Minimalist Loader',
        'Minimalist Loader',
        'manage_options',
        'minimalist-loader',
        'minimalist_loader_settings_page'
    );
}
add_action('admin_menu', 'minimalist_loader_add_admin_menu');

function minimalist_loader_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Configurações do Minimalist Loader</h1>
        <form method="post" action="options.php">
            <?php settings_fields('minimalist_loader_settings_group'); ?>
            <?php do_settings_sections('minimalist_loader_settings_group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Cor do Spinner</th>
                    <td><input type="color" name="minimalist_loader_spinner_color"
                            value="<?php echo esc_attr(get_option('minimalist_loader_spinner_color', '#000000')); ?>"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cor do Fundo do Spinner</th>
                    <td><input type="color" name="minimalist_loader_spinner_bg_color"
                            value="<?php echo esc_attr(get_option('minimalist_loader_spinner_bg_color', '#ffffff')); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cor do Fundo da Tela</th>
                    <td><input type="text" name="minimalist_loader_background_color"
                            value="<?php echo esc_attr(get_option('minimalist_loader_background_color', 'rgba(255,255,255,0.8)')); ?>"
                            placeholder="rgba(255,255,255,0.8)"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Usar blur no fundo?</th>
                    <td>
                        <select name="minimalist_loader_use_blur">
                            <option value="1" <?php selected(get_option('minimalist_loader_use_blur', '1'), '1'); ?>>Sim
                            </option>
                            <option value="0" <?php selected(get_option('minimalist_loader_use_blur', '1'), '0'); ?>>Não
                            </option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Tempo mínimo de exibição (ms)</th>
                    <td><input type="number" min="0" name="minimalist_loader_min_time"
                            value="<?php echo esc_attr(get_option('minimalist_loader_min_time', '1000')); ?>"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Tempo máximo de exibição (ms)</th>
                    <td><input type="number" min="0" name="minimalist_loader_max_time"
                            value="<?php echo esc_attr(get_option('minimalist_loader_max_time', '5000')); ?>"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Evento do Google Ad Manager para aguardar</th>
                    <td>
                        <input type="text" name="minimalist_loader_gpt_event"
                            value="<?php echo esc_attr(get_option('minimalist_loader_gpt_event', '')); ?>"
                            placeholder="Ex: slotRenderEnded">
                        <p class="description">
                            Caso queira aguardar um evento GPT específico (ex: <code>slotRenderEnded</code>), insira aqui.
                            Consulte a <a
                                href="https://developers.google.com/publisher-tag/reference?hl=pt-br#googletag.events.SlotRenderEndedEvent"
                                target="_blank">documentação do Google Publisher Tag</a> para ver outros eventos
                            disponíveis.
                            Deixe vazio para usar apenas o carregamento inicial (<code>googletag.apiReady</code>) e o tempo
                            máximo.
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Tipo de Anúncio</th>
                    <td>
                        <select name="minimalist_loader_ad_type">
                            <option value="admanager" <?php selected(get_option('minimalist_loader_ad_type', 'admanager'), 'admanager'); ?>>Google Ad Manager</option>
                            <option value="adsense" <?php selected(get_option('minimalist_loader_ad_type', 'admanager'), 'adsense'); ?>>Google AdSense</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top" class="adsense-option">
                    <th scope="row">Slot ID do AdSense</th>
                    <td>
                        <input type="text" name="minimalist_loader_adsense_slot"
                            value="<?php echo esc_attr(get_option('minimalist_loader_adsense_slot', '')); ?>"
                            placeholder="Ex: 9185880051">
                        <p class="description">
                            Insira o ID do slot do AdSense que você deseja aguardar carregar (ex: data-ad-slot="9185880051")
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function minimalist_loader_show_preloader()
{
    $spinner_color = get_option('minimalist_loader_spinner_color', '#000000');
    $spinner_bg_color = get_option('minimalist_loader_spinner_bg_color', '#ffffff');
    $background_color = get_option('minimalist_loader_background_color', 'rgba(255,255,255,0.8)');
    $use_blur = get_option('minimalist_loader_use_blur', '1');
    $min_time = (int) get_option('minimalist_loader_min_time', 1000);
    $max_time = (int) get_option('minimalist_loader_max_time', 5000);
    $gpt_event = get_option('minimalist_loader_gpt_event', '');

    $blur_css = $use_blur == '1' ? 'backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px);' : '';

    $inline_css = "
    html.minimalist-loader-active {
        overflow: hidden !important;
    }
    #minimalist-loader-container {
        position: fixed;
        z-index: 999999;
        top:0; left:0; right:0; bottom:0;
        background: {$background_color};
        display: flex;
        justify-content: center;
        align-items: center;
        {$blur_css}
    }
    #minimalist-loader-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid {$spinner_bg_color};
        border-top: 5px solid {$spinner_color};
        border-radius: 50%;
        animation: minimalist-spin 1s linear infinite;
    }
    @keyframes minimalist-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    ";

    echo '<div id="minimalist-loader-container"><div id="minimalist-loader-spinner"></div></div>';
    echo '<style type="text/css">' . $inline_css . '</style>';

    ?>
    <script type="text/javascript">
        (function () {
            document.documentElement.classList.add('minimalist-loader-active');

            const minTime = <?php echo $min_time; ?>;
            const maxTime = <?php echo $max_time; ?>;
            const adType = "<?php echo esc_js(get_option('minimalist_loader_ad_type', 'admanager')); ?>";
            const adsenseSlot = "<?php echo esc_js(get_option('minimalist_loader_adsense_slot', '')); ?>";
            const startTime = Date.now();
            const gptEvent = "<?php echo esc_js($gpt_event); ?>";
            let eventTriggered = false;

            function hideLoader() {
                const container = document.getElementById('minimalist-loader-container');
                if (container) {
                    container.style.transition = 'opacity 0.3s ease';
                    container.style.opacity = '0';
                    setTimeout(function () {
                        if (container.parentNode) {
                            container.parentNode.removeChild(container);
                        }
                        document.documentElement.classList.remove('minimalist-loader-active');
                    }, 300);
                }
            }

            function checkAdsenseLoaded() {
                if (!window.adsbygoogle) return false;
                const ads = document.querySelectorAll('ins.adsbygoogle');
                for (let ad of ads) {
                    if (ad.dataset.adSlot === adsenseSlot && ad.dataset.adsbygoogleStatus === 'done') {
                        return true;
                    }
                }
                return false;
            }

            function conditionsMet() {
                const elapsed = Date.now() - startTime;

                if (adType === 'adsense') {
                    return (elapsed >= minTime && (checkAdsenseLoaded() || elapsed >= maxTime));
                } else {
                    const adManagerReady = (typeof googletag !== 'undefined' && googletag.apiReady === true);
                    if (!gptEvent) {
                        return (elapsed >= minTime && (adManagerReady || elapsed >= maxTime));
                    } else {
                        return (elapsed >= minTime && (eventTriggered || elapsed >= maxTime));
                    }
                }
            }

            function checkCondition() {
                if (conditionsMet()) {
                    hideLoader();
                } else {
                    requestAnimationFrame(checkCondition);
                }
            }

            if (adType === 'admanager' && gptEvent) {
                const interval = setInterval(function () {
                    if (typeof googletag !== 'undefined' && googletag.apiReady === true && googletag.pubads) {
                        clearInterval(interval);
                        googletag.cmd.push(function () {
                            try {
                                googletag.pubads().addEventListener(gptEvent, function () {
                                    eventTriggered = true;
                                });
                            } catch (e) {
                                console.warn("Falha ao registrar listener do evento GPT: ", e);
                            }
                        });
                    }
                }, 100);
            }

            requestAnimationFrame(checkCondition);
        })();
    </script>
    <?php
}
add_action('wp_footer', 'minimalist_loader_show_preloader', 1);
