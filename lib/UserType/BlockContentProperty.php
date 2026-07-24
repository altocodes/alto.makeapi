<?php

namespace Alto\MakeApi\UserType;

use Alto\MakeApi\Dto\Entity\Iblock\Property\TextDto;
use Alto\MakeApi\Dto\UserType\BlockContentDto;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CFileMan;
use CUtil;

Loc::loadMessages(__FILE__);

/**
 * Заголовок блока — в колонке DESCRIPTION значения свойства (рекомендуется WITH_DESCRIPTION = N).
 * Заголовок, тело, тип текста и сортировка — JSON в VALUE: {"v":1,"title":"...","content":"...","type":"text|html","sort":int}.
 * Колонка DESCRIPTION дублирует заголовок для совместимости с ядром; в API достаточно сырого VALUE.
 *
 * В форме элемента (FORM_FILL + fileman + use_htmledit) контент — CFileMan::AddHTMLEditorFrame
 * (переключатель текст / HTML / визуальный редактор, как у DETAIL_TEXT).
 */
final class BlockContentProperty
{
    public const USER_TYPE = 'MakeApiBlockContent';

    private const JSON_DB_VERSION = 1;

    private static bool $sortControlScriptEmitted = false;

    public static function GetUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => PropertyTable::TYPE_STRING,
            'USER_TYPE' => self::USER_TYPE,
            'DESCRIPTION' => Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_DESC'),
            'GetPropertyFieldHtml' => [self::class, 'GetPropertyFieldHtml'],
            'GetPropertyFieldHtmlMulty' => [self::class, 'GetPropertyFieldHtmlMulty'],
            'GetAdminListViewHTML' => [self::class, 'GetAdminListViewHTML'],
            'GetPublicViewHTML' => [self::class, 'GetPublicViewHTML'],
            'ConvertToDB' => [self::class, 'ConvertToDB'],
            'ConvertFromDB' => [self::class, 'ConvertFromDB'],
            'GetLength' => [self::class, 'GetLength'],
            'GetUIFilterProperty' => [self::class, 'GetUIFilterProperty'],
        ];
    }

    public static function GetAdminListViewHTML(array $arProperty, array $value, array $strHTMLControlName): string
    {
        $parsed = self::normalizeIncomingValue($arProperty, $value);
        $title = $parsed['title'];
        if ($title !== '') {
            return htmlspecialcharsbx($title);
        }
        $text = strip_tags($parsed['content']);
        if ($text !== '') {
            return htmlspecialcharsbx(mb_substr($text, 0, 120)) . (mb_strlen($text) > 120 ? '…' : '');
        }

        return '&nbsp;';
    }

    /**
     * @return string|BlockContentDto Публичная часть — HTML; при $strHTMLControlName['VIEW'] === 'JSON' (API) — DTO.
     */
    public static function GetPublicViewHTML(array $arProperty, array $value, array $strHTMLControlName): string|BlockContentDto
    {
        $parsed = self::normalizeIncomingValue($arProperty, $value);
        $title = $parsed['title'];
        $content = $parsed['content'];
        $type = $parsed['type'] === 'text' ? 'text' : 'html';
        $sort = (int)($parsed['sort'] ?? 500);
        if ($sort < 0) {
            $sort = 500;
        }

        if (($strHTMLControlName['VIEW'] ?? '') === 'JSON') {
            return new BlockContentDto($title, new TextDto($type, $content), $sort);
        }

        if ($title === '' && $content === '') {
            return '';
        }

        $html = '<div class="makeapi-block-content">';
        if ($title !== '') {
            $html .= '<div class="makeapi-block-content__title">' . htmlspecialcharsbx($title) . '</div>';
        }
        if ($content !== '') {
            $html .= '<div class="makeapi-block-content__body">' . FormatText($content, $type) . '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    public static function GetPropertyFieldHtml(array $arProperty, array $arValue, array $strHTMLControlName): string
    {
        $baseName = (string)($strHTMLControlName['VALUE'] ?? '');
        $descrName = (string)($strHTMLControlName['DESCRIPTION'] ?? '');
        $parsed = self::normalizeIncomingValue($arProperty, $arValue);
        $isMultiple = ($arProperty['MULTIPLE'] ?? 'N') === 'Y';

        return self::renderEditorLayout($baseName, $descrName, $parsed, $isMultiple, $arProperty, $strHTMLControlName);
    }

    /**
     * Множественное свойство: ядро отдаёт значения в порядке ID в БД, а не по sort из JSON.
     * Собираем одну таблицу строк, сортируем по полю sort в VALUE, затем вызываем GetPropertyFieldHtml для каждой строки.
     */
    public static function GetPropertyFieldHtmlMulty(array $arProperty, $values, array $strHTMLControlName): string
    {
        if (($arProperty['MULTIPLE'] ?? 'N') !== 'Y') {
            return '';
        }

        $values = is_array($values) ? $values : [];
        $normalized = self::normalizeMultyValuesMap($values);
        $sortedKeys = self::sortMultyValueKeys(array_keys($normalized), $normalized);
        $sorted = [];
        foreach ($sortedKeys as $k) {
            $sorted[$k] = $normalized[$k];
        }

        $copy = !empty($strHTMLControlName['COPY']);
        if ($copy) {
            $sorted = self::renumberMultyKeysForCopy($sorted);
        }

        $base = (string)$strHTMLControlName['VALUE'];
        $descrBase = (string)($strHTMLControlName['DESCRIPTION'] ?? $base);
        $formName = (string)($strHTMLControlName['FORM_NAME'] ?? 'form_element');
        $mode = (string)($strHTMLControlName['MODE'] ?? 'FORM_FILL');
        $isFormFill = $mode === 'FORM_FILL';

        $tableId = 'tb' . md5($base);
        $html = '<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="'
            . htmlspecialcharsbx($tableId) . '">';

        $maxN = self::maxNewStyleKeyIndex(array_keys($sorted));

        foreach ($sorted as $key => $val) {
            if (!is_array($val) || !array_key_exists('VALUE', $val)) {
                $val = ['VALUE' => $val, 'DESCRIPTION' => ''];
            }
            $html .= '<tr><td>';
            $html .= self::GetPropertyFieldHtml(
                $arProperty,
                $val,
                [
                    'VALUE' => $base . '[' . $key . '][VALUE]',
                    'DESCRIPTION' => $descrBase . '[' . $key . '][DESCRIPTION]',
                    'FORM_NAME' => $formName,
                    'MODE' => $mode,
                    'COPY' => $copy,
                ]
            );
            $html .= '</td></tr>';
        }

        $propId = (int)$arProperty['ID'];
        $fromPost = isset($_REQUEST['PROP'][$propId]) && is_array($_REQUEST['PROP'][$propId]);

        if ($isFormFill && !$copy && !$fromPost) {
            $cnt = (int)($arProperty['MULTIPLE_CNT'] ?? 5);
            if ($cnt <= 0 || $cnt > 30) {
                $cnt = 5;
            }
            for ($i = 0; $i < $cnt; $i++) {
                $newKey = 'n' . ($maxN + 1 + $i);
                $emptyVal = ['VALUE' => '', 'DESCRIPTION' => ''];
                $html .= '<tr><td>';
                $html .= self::GetPropertyFieldHtml(
                    $arProperty,
                    $emptyVal,
                    [
                        'VALUE' => $base . '[' . $newKey . '][VALUE]',
                        'DESCRIPTION' => $descrBase . '[' . $newKey . '][DESCRIPTION]',
                        'FORM_NAME' => $formName,
                        'MODE' => $mode,
                        'COPY' => false,
                    ]
                );
                $html .= '</td></tr>';
            }
        }

        if ($isFormFill) {
            $addLabel = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_ADD_ROW') ?: 'Добавить';
            $html .= '<tr><td><input type="button" class="adm-btn" value="' . htmlspecialcharsbx($addLabel)
                . '" onClick="BX.IBlock.Tools.addNewRow(\'' . CUtil::JSEscape($tableId) . '\')"></td></tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * @param array $property
     * @param array $strHTMLControlName
     * @param array $fields
     */
    public static function GetUIFilterProperty($property, $strHTMLControlName, &$fields): void
    {
        $fields['type'] = 'string';
        $fields['operators'] = [
            'default' => '%',
        ];
        $fields['filterable'] = '?';
    }

    public static function ConvertToDB(array $arProperty, array $value): array|false
    {
        if (!isset($value['VALUE']) || !is_array($value['VALUE'])) {
            return false;
        }

        $inner = $value['VALUE'];
        $title = trim((string)($value['DESCRIPTION'] ?? ''));
        if ($title === '' && isset($inner['TITLE'])) {
            $title = trim((string)$inner['TITLE']);
        }

        $content = '';
        $contentType = 'html';
        if (array_key_exists('TEXT', $inner)) {
            $content = (string)$inner['TEXT'];
            $contentType = mb_strtolower((string)($inner['TYPE'] ?? 'html'));
        } else {
            $content = (string)($inner['CONTENT'] ?? '');
            $contentType = mb_strtolower((string)($inner['CONTENT_TYPE'] ?? $inner['TYPE'] ?? 'html'));
        }

        if ($contentType !== 'text') {
            $contentType = 'html';
        }

        if (Loader::includeModule('bitrix24')) {
            $sanitizer = new \CBXSanitizer();
            $sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
            $sanitizer->ApplyDoubleEncode(false);
            $content = $sanitizer->SanitizeHtml($content);
        }

        $content = trim($content);
        if ($title === '' && $content === '') {
            return false;
        }

        // SORT нельзя класть в VALUE[SORT]: визуальный редактор добавляет hidden name="…[VALUE]" value="",
        // из‑за этого в POST поле VALUE становится строкой и [VALUE][SORT] теряется — в JSON попадал sort=500 у всех.
        $sort = 500;
        if (array_key_exists('SORT', $value) && $value['SORT'] !== '' && $value['SORT'] !== null) {
            $sort = (int)$value['SORT'];
        } elseif (isset($inner['SORT'])) {
            $sort = (int)$inner['SORT'];
        }
        if ($sort < 0) {
            $sort = 500;
        }

        $payload = [
            'v' => self::JSON_DB_VERSION,
            'title' => $title,
            'content' => $content,
            'type' => $contentType,
            'sort' => $sort,
        ];

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException) {
            return false;
        }

        global $DB;
        $limit = ($DB->type === 'MYSQL') ? 63200 : 1950;
        if (mb_strlen($json) > $limit) {
            $payload['content'] = mb_substr($payload['content'], 0, max(0, mb_strlen($payload['content']) - (mb_strlen($json) - $limit)));
            try {
                $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException) {
                return false;
            }
        }

        return [
            'VALUE' => $json,
            'DESCRIPTION' => $title,
        ];
    }

    public static function ConvertFromDB(array $arProperty, array $value): array
    {
        $raw = isset($value['VALUE']) ? (string)$value['VALUE'] : '';
        $parsed = self::decodeStored($raw);
        // Старые записи: заголовок только в DESCRIPTION, в JSON ещё нет title
        if (trim($parsed['title']) === '') {
            $descrTitle = trim((string)($value['DESCRIPTION'] ?? ''));
            if ($descrTitle !== '') {
                $parsed['title'] = $descrTitle;
            }
        }

        $typeForFrame = $parsed['type'] === 'text' ? 'text' : 'html';

        return [
            'VALUE' => [
                'TEXT' => $parsed['content'],
                'TYPE' => $typeForFrame,
                'SORT' => $parsed['sort'],
            ],
            'DESCRIPTION' => $parsed['title'],
        ];
    }

    public static function GetLength(array $arProperty, array $value): int
    {
        if (!isset($value['VALUE'])) {
            return 0;
        }
        if (is_string($value['VALUE'])) {
            return mb_strlen($value['VALUE']);
        }
        if (is_array($value['VALUE'])) {
            $converted = self::ConvertToDB($arProperty, $value);

            return is_array($converted) && isset($converted['VALUE']) ? mb_strlen((string)$converted['VALUE']) : 0;
        }

        return 0;
    }

    private static function renderEditorLayout(
        string $valueBaseName,
        string $descrControlName,
        array $parsed,
        bool $showRemoveRow,
        array $arProperty,
        array $strHTMLControlName
    ): string {
        $titleLabel = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_TITLE_LABEL');
        $contentLabel = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_BODY_LABEL');
        $removeLabel = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_REMOVE_ROW');
        $sortUp = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_SORT_UP');
        $sortDown = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_SORT_DOWN');
        $sortHint = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_SORT_HINT');
        $title = htmlspecialcharsbx($parsed['title']);
        $content = $parsed['content'];
        $contentType = $parsed['type'] === 'text' ? 'text' : 'html';
        $sortVal = (int)($parsed['sort'] ?? 0);
        if ($sortVal <= 0) {
            $sortVal = self::defaultSortFromValueBaseName($valueBaseName);
        }

        $inner = '<div class="makeapi-ut-block-fields" style="max-width:100%">';
        if ($showRemoveRow) {
            $inner .= '<div style="margin:0 0 10px;text-align:right">';
            $inner .= '<input type="button" class="adm-btn" value="' . htmlspecialcharsbx($removeLabel) . '" ';
            $inner .= 'onclick="(function(btn){var e=btn;while(e&&e.tagName&&e.tagName.toUpperCase()!==\'TR\'){e=e.parentNode;}var p=e&&e.parentNode;if(e&&p){p.removeChild(e);}if(typeof BX!==\'undefined\'){BX.onCustomEvent(\'onAdminTabsChange\');}if(window.AltoMakeApiBlockSort){AltoMakeApiBlockSort.recalc(p);}})(this)">';
            $inner .= '</div>';
        }

        $titleName = $descrControlName !== '' ? $descrControlName : $valueBaseName . '[TITLE]';
        $inner .= '<div style="margin-bottom:12px">';
        $inner .= '<label style="display:block;margin-bottom:4px">' . $titleLabel . '</label>';
        $inner .= '<input type="text" style="width:100%;max-width:900px" name="'
            . htmlspecialcharsbx($titleName) . '" value="' . $title . '">';
        $inner .= '</div>';

        $inner .= '<div>';
        $inner .= '<label style="display:block;margin-bottom:4px">' . $contentLabel . '</label>';
        $inner .= self::renderContentArea($valueBaseName, $content, $contentType, $arProperty, $strHTMLControlName);
        $inner .= '</div></div>';

        $sortFieldName = htmlspecialcharsbx(self::sortHtmlControlNameFromValueBase($valueBaseName));
        $hiddenSort = '<input type="hidden" class="makeapi-ut-sort-input" name="' . $sortFieldName . '" value="' . $sortVal . '">';

        $prefix = '';
        if ($showRemoveRow) {
            $prefix = self::emitSortControlScriptOnce();
            // Не использовать вложенную <table><tr>: кнопки сортировки ищут первый TR вверх по DOM —
            // это была бы внутренняя строка без соседей; нужна одна ячейка внешней строки Bitrix.
            $sortCol = '<div class="makeapi-ut-sort-col adm-detail-valign-top" style="flex:0 0 auto;width:52px;padding-right:10px" title="'
                . htmlspecialcharsbx($sortHint) . '">'
                . $hiddenSort
                . '<div style="display:flex;flex-direction:column;gap:4px;align-items:center">'
                . '<input type="button" class="adm-btn" style="min-width:36px;padding:2px 6px" value="&#9650;" '
                . 'title="' . htmlspecialcharsbx($sortUp) . '" '
                . 'onclick="AltoMakeApiBlockSort.moveRow(this,-1)">'
                . '<input type="button" class="adm-btn" style="min-width:36px;padding:2px 6px" value="&#9660;" '
                . 'title="' . htmlspecialcharsbx($sortDown) . '" '
                . 'onclick="AltoMakeApiBlockSort.moveRow(this,1)">'
                . '</div></div>';
            $html = '<div class="makeapi-ut-block-layout" style="display:flex;flex-direction:row;align-items:flex-start;gap:10px;width:100%;box-sizing:border-box">'
                . $sortCol
                . '<div class="makeapi-ut-block-main" style="flex:1;min-width:0">' . $inner . '</div>'
                . '</div>';

            return $prefix . $html;
        }

        return $inner;
    }

    private static function emitSortControlScriptOnce(): string
    {
        if (self::$sortControlScriptEmitted) {
            return '';
        }
        self::$sortControlScriptEmitted = true;

        return '<script>
(function(){
if(window.AltoMakeApiBlockSort){return;}
window.AltoMakeApiBlockSort={
findTr:function(el){
	var e=el;
	while(e&&e.tagName&&e.tagName.toUpperCase()!=="TR"){e=e.parentNode;}
	return e||null;
},
isDataRow:function(tr){
	if(!tr||!tr.cells||tr.cells.length<1){return false;}
	return !!tr.querySelector("input.makeapi-ut-sort-input");
},
moveRow:function(btn,dir){
	var tr=this.findTr(btn);
	if(!tr||!tr.parentNode){return;}
	var p=tr.parentNode;
	if(dir<0){
		var sib=tr.previousElementSibling;
		while(sib&&!this.isDataRow(sib)){sib=sib.previousElementSibling;}
		if(!sib){return;}
		p.insertBefore(tr,sib);
	}else{
		var sib=tr.nextElementSibling;
		while(sib&&!this.isDataRow(sib)){sib=sib.nextElementSibling;}
		if(!sib){return;}
		var after=sib.nextElementSibling;
		if(after){p.insertBefore(tr,after);}else{p.appendChild(tr);}
	}
	this.recalc(tr);
	if(typeof BX!=="undefined"){BX.onCustomEvent("onAdminTabsChange");}
},
recalc:function(anchor){
	var p=null;
	if(anchor&&anchor.tagName&&anchor.tagName.toUpperCase()==="TBODY"){
		p=anchor;
	}else{
		var tr=this.findTr(anchor);
		p=tr&&tr.parentNode;
	}
	if(!p||!p.rows){return;}
	var step=10,n=step;
	for(var i=0;i<p.rows.length;i++){
		var r=p.rows[i];
		if(!this.isDataRow(r)){continue;}
		var inp=r.querySelector("input.makeapi-ut-sort-input");
		if(inp){inp.value=String(n);n+=step;}
	}
}
};
})();
</script>';
    }

    private static function defaultSortFromValueBaseName(string $valueBaseName): int
    {
        if (preg_match('/\\[([^\\]]+)\\]\\[VALUE\\]$/', $valueBaseName, $m)) {
            $key = $m[1];
            if (preg_match('/^n(\\d+)$/', $key, $n)) {
                return 100000 + (int)$n[1];
            }
            if (ctype_digit($key)) {
                return (int)$key * 10;
            }
        }

        return 500;
    }

    /**
     * Имя поля сортировки на одном уровне с [VALUE] и [DESCRIPTION]: PROP[ID][rowKey][SORT].
     */
    private static function sortHtmlControlNameFromValueBase(string $valueBaseName): string
    {
        if (preg_match('/^(.*)\[VALUE\]$/', $valueBaseName, $m)) {
            return $m[1] . '[SORT]';
        }

        return $valueBaseName . '[SORT]';
    }

    private static function renderContentArea(
        string $baseName,
        string $content,
        string $contentType,
        array $arProperty,
        array $strHTMLControlName
    ): string {
        $mode = (string)($strHTMLControlName['MODE'] ?? '');
        $useFrame = $mode === 'FORM_FILL'
            && Option::get('iblock', 'use_htmledit') === 'Y'
            && Loader::includeModule('fileman');

        if ($useFrame) {
            $textField = preg_replace('/([^a-z0-9])/is', '_', $baseName . '[TEXT]');
            $typeField = preg_replace('/([^a-z0-9])/is', '_', $baseName . '[TYPE]');
            $typeVal = $contentType === 'text' ? 'text' : 'html';
            $editorKey = 'makeapi_block_' . (int)$arProperty['ID'] . '_' . md5($baseName);

            ob_start();
            echo '<input type="hidden" name="' . htmlspecialcharsbx($baseName) . '" value="">';
            CFileMan::AddHTMLEditorFrame(
                $textField,
                $content,
                $typeField,
                $typeVal,
                [
                    'height' => 320,
                    'width' => '100%',
                ],
                'N',
                0,
                '',
                '',
                defined('SITE_ID') ? SITE_ID : '',
                true,
                false,
                [
                    'toolbarConfig' => CFileMan::GetEditorToolbarConfig('iblock_admin'),
                    'saveEditorKey' => $editorKey,
                ]
            );

            return (string)ob_get_clean();
        }

        return self::renderFallbackTextarea($baseName, $content, $contentType);
    }

    private static function renderFallbackTextarea(string $baseName, string $content, string $contentType): string
    {
        $isText = $contentType === 'text';
        $typeName = htmlspecialcharsbx($baseName) . '[TYPE]';
        $bodyName = htmlspecialcharsbx($baseName) . '[TEXT]';

        $labelText = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_TYPE_TEXT');
        $labelHtml = Loc::getMessage('MAKEAPI_UT_BLOCK_CONTENT_TYPE_HTML');

        return '
			<div style="margin-bottom:8px">
				<label><input type="radio" name="' . $typeName . '" value="text"' . ($isText ? ' checked' : '') . '> ' . $labelText . '</label>
				&nbsp;
				<label><input type="radio" name="' . $typeName . '" value="html"' . (!$isText ? ' checked' : '') . '> ' . $labelHtml . '</label>
			</div>
			<textarea class="typearea" style="width:100%;min-height:220px" name="' . $bodyName . '">'
            . htmlspecialcharsbx($content) . '</textarea>';
    }

    private static function normalizeIncomingValue(array $arProperty, array $value): array
    {
        $titleFromDescr = trim((string)($value['DESCRIPTION'] ?? ''));

        if (!is_array($value['VALUE'] ?? null)) {
            $parsed = self::decodeStored(isset($value['VALUE']) ? (string)$value['VALUE'] : '');
            if (trim($parsed['title']) === '' && $titleFromDescr !== '') {
                $parsed['title'] = $titleFromDescr;
            }
            if (($parsed['sort'] ?? 0) <= 0) {
                $parsed['sort'] = 500;
            }

            return $parsed;
        }

        $inner = $value['VALUE'];
        $title = trim((string)($inner['TITLE'] ?? ''));
        if ($title === '' && $titleFromDescr !== '') {
            $title = $titleFromDescr;
        }

        $body = (string)($inner['TEXT'] ?? $inner['CONTENT'] ?? '');
        $type = mb_strtolower((string)($inner['TYPE'] ?? $inner['CONTENT_TYPE'] ?? 'html'));
        $sort = isset($inner['SORT']) ? (int)$inner['SORT'] : 0;
        if ($sort <= 0 && array_key_exists('SORT', $value) && $value['SORT'] !== '' && $value['SORT'] !== null) {
            $sort = (int)$value['SORT'];
        }

        return [
            'title' => $title,
            'content' => $body,
            'type' => $type === 'text' ? 'text' : 'html',
            'sort' => $sort,
        ];
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<int|string, array{VALUE: mixed, DESCRIPTION: string}>
     */
    private static function normalizeMultyValuesMap(array $values): array
    {
        $out = [];
        foreach ($values as $key => $row) {
            if (!is_array($row)) {
                $out[$key] = ['VALUE' => $row, 'DESCRIPTION' => ''];
                continue;
            }
            if (array_key_exists('VALUE', $row)) {
                $item = [
                    'VALUE' => $row['VALUE'],
                    'DESCRIPTION' => (string)($row['DESCRIPTION'] ?? ''),
                ];
                if (array_key_exists('SORT', $row) && $row['SORT'] !== '' && $row['SORT'] !== null) {
                    $item['SORT'] = (int)$row['SORT'];
                }
                $out[$key] = $item;
                continue;
            }
            $out[$key] = [
                'VALUE' => $row['VALUE'] ?? $row['~VALUE'] ?? '',
                'DESCRIPTION' => (string)($row['DESCRIPTION'] ?? $row['~DESCRIPTION'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param list<int|string> $keys
     * @param array<int|string, array{VALUE: mixed, DESCRIPTION: string}> $normalized
     * @return list<int|string>
     */
    private static function sortMultyValueKeys(array $keys, array $normalized): array
    {
        usort($keys, static function ($ka, $kb) use ($normalized) {
            $cmp = self::extractSortFromValuePayload($normalized[$ka])
                <=> self::extractSortFromValuePayload($normalized[$kb]);
            if ($cmp !== 0) {
                return $cmp;
            }

            return self::comparePropertyValueKeys((string)$ka, (string)$kb);
        });

        return $keys;
    }

    /**
     * @param array<int|string, array{VALUE: mixed, DESCRIPTION: string}> $sorted
     * @return array<string, array{VALUE: mixed, DESCRIPTION: string}>
     */
    private static function renumberMultyKeysForCopy(array $sorted): array
    {
        $out = [];
        $i = 0;
        foreach ($sorted as $v) {
            $out['n' . $i] = $v;
            $i++;
        }

        return $out;
    }

    /**
     * @param list<int|string> $keys
     */
    private static function maxNewStyleKeyIndex(array $keys): int
    {
        $max = -1;
        foreach ($keys as $key) {
            if (preg_match('/^n(\d+)$/', (string)$key, $m)) {
                $max = max($max, (int)$m[1]);
            }
        }

        return $max;
    }

    /**
     * @param array{VALUE: mixed, DESCRIPTION: string} $entry
     */
    private static function extractSortFromValuePayload(array $entry): int
    {
        if (array_key_exists('SORT', $entry) && $entry['SORT'] !== '' && $entry['SORT'] !== null) {
            return (int)$entry['SORT'];
        }
        $v = $entry['VALUE'] ?? null;
        if (is_array($v)) {
            return isset($v['SORT']) ? (int)$v['SORT'] : 500;
        }

        $parsed = self::decodeStored((string)$v);

        return (int)($parsed['sort'] ?? 500);
    }

    private static function comparePropertyValueKeys(string $ka, string $kb): int
    {
        $ida = ctype_digit($ka) ? (int)$ka : null;
        $idb = ctype_digit($kb) ? (int)$kb : null;
        if ($ida !== null || $idb !== null) {
            return ($ida ?? PHP_INT_MAX) <=> ($idb ?? PHP_INT_MAX);
        }

        preg_match('/^n(\d+)$/', $ka, $ma);
        preg_match('/^n(\d+)$/', $kb, $mb);
        $na = isset($ma[1]) ? (int)$ma[1] : PHP_INT_MAX;
        $nb = isset($mb[1]) ? (int)$mb[1] : PHP_INT_MAX;

        return $na <=> $nb;
    }

    /**
     * @return array{title: string, content: string, type: string, sort: int}
     */
    private static function decodeStored(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['title' => '', 'content' => '', 'type' => 'html', 'sort' => 500];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['title' => '', 'content' => $raw, 'type' => 'html', 'sort' => 500];
        }

        if (array_key_exists('content', $decoded) || array_key_exists('title', $decoded)) {
            $type = mb_strtolower((string)($decoded['type'] ?? 'html'));
            $sort = (int)($decoded['sort'] ?? 500);
            if ($sort <= 0) {
                $sort = 500;
            }

            return [
                'title' => (string)($decoded['title'] ?? ''),
                'content' => (string)($decoded['content'] ?? ''),
                'type' => $type === 'text' ? 'text' : 'html',
                'sort' => $sort,
            ];
        }

        return ['title' => '', 'content' => $raw, 'type' => 'html', 'sort' => 500];
    }
}
