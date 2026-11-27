<?php

/**
 * Russian language file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author Aleksandr Selivanov <alexgearbox@yandex.ru>
 */

/** Строки интерфейса калькулятора */
$lang['locale']               = 'ru-RU';
$lang['loading']              = 'Загрузка калькулятора...';
$lang['load_error']           = 'Не удалось загрузить калькулятор.';
$lang['calculate']            = 'Вычислить';
$lang['copy']                 = 'Копировать результат';
$lang['copied']               = 'Результат скопирован.';
$lang['copy_error']           = 'Не удалось скопировать.';
$lang['copy_select']          = 'Результат выделен. Нажмите Ctrl+C для копирования.';
$lang['number_hint']          = 'Запятая или точка как десятичный разделитель; разделители тысяч не допускаются.';
$lang['list_hint']            = 'По одному числу в строке. Пустые строки сохраняются.';
$lang['invalid_number']       = 'Введите корректное число.';
$lang['range_error']          = 'Значение вне допустимого диапазона.';
$lang['line']                 = 'Строка';
$lang['division_zero']        = 'Деление на ноль недопустимо.';
$lang['invalid_expression']   = 'Некорректное выражение в определении.';
$lang['unknown_variable']     = 'Неизвестная переменная в определении.';
$lang['nonfinite']            = 'Результат не определён или слишком велик.';
$lang['empty_list']           = 'Введите хотя бы одно число в списке.';
$lang['required']             = 'Введите значение.';
$lang['result']               = 'Результат';
$lang['too_many_lines']       = 'Допускается не более 10 000 строк и 200 000 символов.';
$lang['javascript_required']  = 'Для работы этого калькулятора необходим JavaScript.';
$lang['invalid_name']         = 'Недопустимое имя определения.';
$lang['access_denied']        = 'У вас нет прав на чтение калькулятора или страницы его определений.';
$lang['missing_page']         = 'Указанная страница определений не существует.';
$lang['missing_definition']   = 'Определение калькулятора не найдено:';

// Диагностика парсера определений (выводится через DokuWiki getLang()).
$lang['definition_page_too_large']        = 'Страница определений слишком велика (максимум 1 МиБ).';
$lang['definition_duplicate_name']        = 'Повторяющееся имя определения: %s';
$lang['definition_too_many']              = 'На странице допускается не более %d определений.';
$lang['definition_duplicate_title']       = 'Заголовок указан более одного раза.';
$lang['definition_reserved_field']        = 'Повторяющееся или зарезервированное имя поля: %s';
$lang['definition_duplicate_property']    = 'Свойство указано более одного раза: %s';
$lang['definition_invalid_line']          = 'Неизвестная строка определения или неверный отступ.';
$lang['definition_invalid_title']         = 'Требуется заголовок длиной не более %d байтов.';
$lang['definition_too_many_fields']       = 'Допускается не более %d полей.';
$lang['definition_unknown_property']      = 'Неизвестное свойство в %s.';
$lang['definition_invalid_label']         = 'Подпись отсутствует или слишком длинная: %s (не более %d байтов).';
$lang['definition_unit_too_long']         = 'Единица слишком длинная (не более %d байтов).';
$lang['definition_invalid_type']          = 'Тип входа должен быть decimal, decimal-list, regex-list или const.';
$lang['definition_invalid_range']         = 'Минимум больше максимума.';
$lang['definition_default_out_of_range']  = 'Значение по умолчанию вне допустимого диапазона.';
$lang['definition_invalid_formula']       = 'Отсутствует корректная формула или она слишком длинная (не более %d байтов).';
$lang['definition_formula_character']     = 'Недопустимый символ в выражении.';
$lang['definition_invalid_decimals']      = 'Значение decimals должно быть от %d до %d.';
$lang['definition_invalid_structure']     = 'Требуется от %d до %d выводов, не более %d входов и не более одного списка значений.';
$lang['definition_invalid_source']        = 'Источник вывода должен быть списком того же типа.';
$lang['definition_invalid_number']        = 'Недопустимое числовое значение: %s';
$lang['definition_number_too_large']      = 'Числовое значение слишком велико.';
$lang['definition_line']                  = 'Строка определения %d: %s';

$lang['definition_invalid_output_type']   = 'Тип вывода должен быть decimal, decimal-list или text-list.';
$lang['definition_scalar_source']         = 'Скалярный вывод типа decimal не должен иметь свойство source.';

$lang['definition_expression_syntax']     = 'Некорректное выражение: проверьте операторы, числа и скобки.';
$lang['definition_unknown_variable']      = 'Неизвестная или недоступная переменная: %s';
$lang['definition_unknown_function']      = 'Неизвестная функция: %s';
$lang['definition_function_arguments']    = 'Функция %s ожидает от %d до %d аргументов.';

$lang['calculation_basis']                = 'Основа расчёта';
$lang['constant_name']                    = 'Константа';
$lang['constant_value']                   = 'Значение';
$lang['constant_scope']                   = 'Область';
$lang['scope_local']                      = 'Локальная';
$lang['scope_global']                     = 'Глобальная';
$lang['global_constants']                 = 'Глобальные константы';
$lang['constants_multiple_blocks']        = 'На странице определений допускается только один блок calcconstants.';
$lang['constants_invalid_line']           = 'Некорректная глобальная константа; ожидается имя = число.';
$lang['constants_duplicate_name']         = 'Глобальная константа определена более одного раза: %s';
$lang['constants_too_many']               = 'Допускается не более %d глобальных констант.';
$lang['constants_value_required']         = 'Для константы %s требуется значение (value).';

$lang['chem_title']               = 'Массовые доли элементов';
$lang['chem_formula']             = 'Химическая формула';
$lang['chem_part']                = 'Доля (%) — пусто = 100';
$lang['chem_hint']                = 'Примеры: TiO2, Ca(OH)2, CuSO4·5H2O. Обозначения элементов регистрозависимы.';
$lang['chem_molar_mass']          = 'Молярная масса';
$lang['chem_total']               = 'Сумма массовых долей';
$lang['chem_rounding']            = 'Значения в таблице округлены.';
$lang['chem_element']             = 'Элемент';
$lang['chem_count']               = 'Число атомов';
$lang['chem_weight']              = 'Атомная масса (а. е. м.)';
$lang['chem_percent']             = 'Массовая доля (%)';
$lang['chem_ppm']                 = 'Массовая доля (ppm)';
$lang['chem_copy_table']          = 'Копировать таблицу';
$lang['chem_copy_cell']           = 'Двойной щелчок или Enter копирует неокруглённое числовое значение.';
$lang['chem_enter_formula']       = 'Введите химическую формулу.';
$lang['chem_part_error']          = 'Введите долю от 0 до 100 %.';
$lang['chem_too_long']            = 'Формула слишком длинная (не более 2000 символов).';
$lang['chem_invalid_count']       = 'Индексы и коэффициенты должны быть целыми положительными числами.';
$lang['chem_too_large']           = 'Числа в формуле слишком велики.';
$lang['chem_too_deep']            = 'Слишком много вложенных скобок.';
$lang['chem_missing_bracket']     = 'Отсутствует закрывающая скобка.';
$lang['chem_unknown_element']     = 'Неизвестный элемент или нет данных о стандартной атомной массе.';
$lang['chem_invalid_character']   = 'Недопустимый символ в формуле.';
$lang['chem_empty_group']         = 'Пустая группа или недостающий компонент формулы.';
$lang['chem_position']            = 'Позиция';
$lang['chem_basis']               = 'M = Σ(n × атомная масса). Массовая доля элемента (%) = n × атомная масса / M × введённая доля. ppm = % × 10000. Молярная масса не зависит от доли. Без доли берётся чистое соединение (100 %).';
$lang['chem_weights_source']      = 'Стандартные атомные массы: центральные значения без учёта неопределённости и изотопного разброса. Источник:';
$lang['chem_fixed']               = 'Фиксированная формула';

$lang['regex_hint']               = 'Именованные числовые группы доступны в формулах списков как row.name. Группа value дополнительно позволяет заменять число в тексте.';
$lang['regex_pattern']            = 'Регулярное выражение';
$lang['regex_processing']         = 'Обработка текста...';
$lang['regex_invalid_pattern']    = 'Некорректное регулярное выражение (не более 512 байтов, без ограничителей /).';
$lang['regex_invalid_flags']      = 'Допустимые флаги регулярного выражения: i и u, каждый не более одного раза.';
$lang['regex_named_group_required'] = 'Регулярное выражение должно содержать хотя бы одну именованную числовую группу, например (?<value>...) или (?<quantity>...).';
$lang['regex_invalid_capture']    = 'Регулярное выражение совпало, но обязательная числовая группа не захвачена.';
$lang['regex_unsupported']        = 'Не удалось запустить обработку: необходимы Web Workers и индексы групп регулярного выражения';
$lang['regex_timeout']            = 'Обработка регулярных выражений остановлена через 2 секунды. Упростите шаблон или ввод.';
$lang['regex_not_ready']          = 'Текст ещё не обработан.';

$lang['definition_invalid_boolean']         = 'Свойство %s должно быть true или false.';
$lang['definition_circular_reference']      = 'Циклическая ссылка между выводами: %s.';
$lang['circular_reference']                 = 'Циклическая ссылка между выводами.';
$lang['definition_invalid_default_text']    = 'Текст по умолчанию должен заканчиваться двойной кавычкой, после которой допускаются только пробельные символы.';
$lang['definition_default_too_large']       = 'Текст по умолчанию может содержать не более %s байтов и %s строк.';

$lang['regex_invalid_group_name']           = 'Недопустимое имя числовой группы: %s. Используйте строчную латинскую букву, за которой следуют строчные латинские буквы, цифры или подчёркивания (не более 64 символов).';
$lang['regex_text_requires_value']          = 'Вывод text-list требует именованную группу value. Для остальных числовых групп используйте вывод decimal-list.';

$lang['definition_invalid_template']        = 'Некорректный шаблон вывода. Используйте одну непустую строку длиной не более %s байтов; допустимы только плейсхолдеры {line}, {label}, {value} и {unit}.';
$lang['invalid_template']                   = 'Некорректный шаблон вывода.';

$lang['definition_template_requires_list']  = 'Плейсхолдер {line} требует спискового вывода со свойством source.';

$lang['definition_exists_argument']         = 'exists() требует ровно один именованный захват регулярного выражения, например exists(row.quantity).';

// Необязательные формулы из полей страниц Struct.
$lang['chem_struct_reference']    = 'Используйте ссылку на поле страницы Struct вида {{$schema.field}} в качестве полной формулы.';
$lang['chem_struct_access']       = 'У вас нет прав на чтение формулы Struct на этой странице.';
$lang['chem_struct_missing']      = 'Для чтения этой формулы требуется совместимая установка Struct.';
$lang['chem_struct_read_error']   = 'Не удалось прочитать формулу из Struct.';
$lang['chem_struct_value']        = 'Поле Struct должно содержать одну непустую формулу в виде текста (не более 2000 байтов). Проверьте схему и имена полей.';
$lang['chem_struct_unsupported']  = 'Этот химический калькулятор поддерживает одно поле Struct на текущей странице, без дополнительных фильтров и псевдонимов схем.';
