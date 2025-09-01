<?php
if (!defined('ABSPATH')) {
    exit;
}

// Список популярных стран и языков
$popular_countries = [
    'RU' => 'Россия',
    'UA' => 'Украина', 
    'BY' => 'Беларусь',
    'KZ' => 'Казахстан',
    'AM' => 'Армения',
    'GE' => 'Грузия',
    'US' => 'США',
    'GB' => 'Великобритания',
    'DE' => 'Германия',
    'FR' => 'Франция',
    'ES' => 'Испания',
    'IT' => 'Италия',
    'CN' => 'Китай',
    'JP' => 'Япония',
    'KR' => 'Южная Корея',
    'BR' => 'Бразилия',
    'IN' => 'Индия',
    'TR' => 'Турция',
    'PL' => 'Польша',
    'NL' => 'Нидерланды'
];

$popular_languages = [
    'en' => 'English',
    'ru' => 'Русский',
    'uk' => 'Українська',
    'hy' => 'Հայերեն',
    'ka' => 'ქართული',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'es' => 'Español',
    'it' => 'Italiano',
    'zh' => '中文',
    'ja' => '日本語',
    'ko' => '한국어',
    'pt' => 'Português',
    'hi' => 'हिन्दी',
    'tr' => 'Türkçe',
    'pl' => 'Polski',
    'nl' => 'Nederlands',
    'ar' => 'العربية',
    'he' => 'עברית',
    'th' => 'ไทย'
];

// Получаем текущий язык сайта
$site_language = $debug_info['site_language'];
$site_language_name = $popular_languages[$site_language] ?? $site_language;
$instance = new Neetrino_Auto_Translate();

// Инициализация настроек по умолчанию - без автоматических значений
if (!isset($default_language) || empty($default_language)) {
    $default_language = ''; // Пустое значение для ручной настройки
}

if (!isset($country_languages)) {
    $country_languages = []; // Пустой массив для ручной настройки
}
?>

<div class="wrap auto-translate-modern">
    <form method="post" action="">
        <?php wp_nonce_field('neetrino_auto_translate_settings'); ?>          <!-- Заголовок -->        <div class="header-section">
            <div class="header-title">
                <div class="title-line">
                    <span class="module-icon dashicons dashicons-translation"></span>
                    <h2>Auto Translate</h2>
                    <div class="site-language-badge">
                        <span class="language-label">Язык сайта:</span>
                        <span class="language-name"><?php echo esc_html($site_language_name); ?></span>
                        <span class="language-code"><?php echo esc_html($site_language); ?></span>
                    </div>
                </div>
                <span class="module-subtitle">Автоматическое определение страны</span>
            </div>            <div class="header-actions">
                <button type="button" id="documentation-btn" class="modern-btn doc-btn">📖 Documentation</button>
            </div>
        </div>        <!-- Таблица языков -->
        <div class="languages-table">            <div class="table-header">
                <div class="col-language">Языки и страны</div>
                <div class="col-url">URL</div>
                <div class="col-code">Код</div>
                <div class="col-actions">Действия</div>
            </div><!-- Default язык -->
            <div class="language-row default-row">
                <div class="col-language">
                    <span class="drag-handle">⋮⋮</span>
                    <select name="default_language" class="language-select modern-select" onchange="updateDefaultLanguagePreview(this)" data-site-language="<?php echo esc_attr($site_language); ?>">
                        <option value="">Выберите язык</option>
                        <?php foreach ($popular_languages as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($default_language, $code); ?> 
                                    <?php echo ($code === $site_language) ? 'data-is-site-language="true"' : ''; ?>>
                                <?php 
                                if ($code === $site_language) {
                                    echo '🏠 ' . esc_html($name) . ' (язык сайта)';
                                } else {
                                    echo esc_html($name);
                                }
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="default-label">Язык для всех стран</span>
                </div>
                <div class="col-url">
                    <code id="default-language-url"><?php echo esc_html($instance->get_language_url($default_language)); ?></code>
                </div>
                <div class="col-code">
                    <span id="default-language-code"><?php echo esc_html($default_language); ?></span>
                </div>
                <div class="col-actions">
                    <!-- Пустая колонка для default языка -->
                </div>
            </div><!-- Исключения для стран -->
            <div id="countries-container">
                <?php if (!empty($country_languages)): ?>
                    <?php 
                    $index = 0;
                    foreach ($country_languages as $country => $language): 
                        $url = $instance->get_language_url($language);
                    ?>
                        <div class="language-row">
                            <div class="col-language">
                                <span class="drag-handle">⋮⋮</span>
                                <select name="country_<?php echo $index; ?>" class="country-select">
                                    <option value="">Выберите страну</option>
                                    <?php foreach ($popular_countries as $code => $name): ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($country, $code); ?>>
                                            <?php echo esc_html($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                →                                <select name="language_<?php echo $index; ?>" class="language-select modern-select" onchange="updateUrlPreview(this)" data-site-language="<?php echo esc_attr($site_language); ?>">
                                    <?php foreach ($popular_languages as $code => $name): ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($language, $code); ?>>
                                            <?php 
                                            if ($code === $site_language) {
                                                echo '🏠 ' . esc_html($name) . ' (язык сайта)';
                                            } else {
                                                echo esc_html($name);
                                            }
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-url">
                                <code><?php echo esc_html($url); ?></code>
                            </div>
                            <div class="col-code">
                                <span><?php echo esc_html($language); ?></span>
                            </div>
                            <div class="col-actions">
                                <button type="button" class="modern-btn remove-btn">🗑️ Удалить</button>
                            </div>
                        </div>
                    <?php $index++; endforeach; ?>
                <?php endif; ?>
            </div>            <!-- Кнопка добавления -->
            <div class="add-language-section">
                <button type="button" id="add-country" class="modern-btn add-btn">+ Добавить исключение для страны</button>
            </div>
        </div>

        <!-- Кнопка сохранения -->
        <div class="save-section">
            <button type="submit" name="submit" class="save-settings-btn">
                Сохранить настройки
            </button>
        </div>
    </form>    <!-- Скрытый шаблон для добавления новых стран -->
    <div id="country-row-template" style="display: none;">
        <div class="language-row">
            <div class="col-language">
                <span class="drag-handle">⋮⋮</span>
                <select name="country_new" class="country-select">
                    <option value="">Выберите страну</option>
                    <?php foreach ($popular_countries as $code => $name): ?>
                        <option value="<?php echo esc_attr($code); ?>">
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                →                <select name="language_new" class="language-select modern-select" onchange="updateUrlPreview(this)" data-site-language="<?php echo esc_attr($site_language); ?>">
                    <?php foreach ($popular_languages as $code => $name): ?>
                        <option value="<?php echo esc_attr($code); ?>">
                            <?php 
                            if ($code === $site_language) {
                                echo '🏠 ' . esc_html($name) . ' (язык сайта)';
                            } else {
                                echo esc_html($name);
                            }
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-url">
                <code>URL будет обновлен</code>
            </div>
            <div class="col-code">
                <span>--</span>
            </div>
            <div class="col-actions">
                <button type="button" class="modern-btn remove-btn">🗑️ Удалить</button>
            </div>
        </div>
    </div>

    <!-- Модальное окно документации -->
    <div id="documentation-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📖 Documentation</h3>
                <button type="button" class="modal-close">×</button>
            </div>            <div class="modal-body">
                <h4>🌍 Как работает Auto Translate</h4>
                <p><strong>Основная функция:</strong> Автоматическое перенаправление пользователей на нужную языковую версию сайта на основе их страны.</p>
                
                <h4>⚙️ Настройка</h4>
                <ul>
                    <li><strong>Язык для всех стран</strong> - базовый язык, используется когда нет исключений для конкретной страны</li>
                    <li><strong>Исключения для стран</strong> - специальные языки для конкретных стран (добавляются вручную)</li>
                </ul>

                <h4><span class="dashicons dashicons-translation" style="font-size: 16px; line-height: 1.2; margin-right: 5px;"></span>URL структура</h4>
                <ul>
                    <li><strong>Язык сайта:</strong> <code>domain.com/</code> (без префикса)</li>
                    <li><strong>Другие языки:</strong> <code>domain.com/LANG/</code> (с префиксом)</li>
                </ul>

                <h4>🎯 Примеры</h4>
                <p>Если установлен базовый язык "English" и добавлено исключение "Россия → Русский", то:</p>
                <ul>
                    <li>Пользователи из России → русская версия</li>
                    <li>Пользователи из других стран → английская версия</li>
                </ul>

                <h4>➕ Добавление исключений</h4>
                <p>Используйте кнопку "+ Добавить исключение для страны" чтобы настроить специальные языки для конкретных стран.</p>
            </div>
        </div>
    </div>

    <!-- AJAX nonce fields -->
    <input type="hidden" id="test_ip_nonce" value="<?php echo wp_create_nonce('neetrino_auto_translate_test'); ?>">
    <input type="hidden" id="clear_cache_nonce" value="<?php echo wp_create_nonce('neetrino_auto_translate_clear_cache'); ?>">
    <input type="hidden" id="site_language" value="<?php echo esc_attr($site_language); ?>">
</div>

<script>
// Передаем язык сайта в JavaScript
window.autoTranslateSiteLanguage = '<?php echo esc_js($site_language); ?>';
</script>
