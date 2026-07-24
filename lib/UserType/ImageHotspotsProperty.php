<?php

namespace Alto\MakeApi\UserType;

use Alto\MakeApi\Dto\UserType\ImageHotspotsValueDto;
use Alto\MakeApi\Helper\FetcherHelper;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Application;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CFile;
use CFileInput;
use CUtil;

Loc::loadMessages(__FILE__);

/**
 * Значение: JSON {"v":2,"x":0-100,"y":0-100} — одна точка на фоне из настроек свойства.
 * Старый формат с markers[] при чтении: берётся первая метка (координаты), elementIds игнорируются.
 */
final class ImageHotspotsProperty
{
    public const USER_TYPE = 'MakeApiImageHotspots';

    private const JSON_DB_VERSION = 2;

    public static function GetUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => PropertyTable::TYPE_STRING,
            'USER_TYPE' => self::USER_TYPE,
            'DESCRIPTION' => Loc::getMessage('MAKEAPI_UT_IMAGE_HOTSPOTS_DESC'),
            'PrepareSettings' => [self::class, 'PrepareSettings'],
            'GetSettingsHTML' => [self::class, 'GetSettingsHTML'],
            'GetPropertyFieldHtml' => [self::class, 'GetPropertyFieldHtml'],
            'GetAdminListViewHTML' => [self::class, 'GetAdminListViewHTML'],
            'GetPublicViewHTML' => [self::class, 'GetPublicViewHTML'],
            'ConvertToDB' => [self::class, 'ConvertToDB'],
            'ConvertFromDB' => [self::class, 'ConvertFromDB'],
            'GetLength' => [self::class, 'GetLength'],
            'GetUIFilterProperty' => [self::class, 'GetUIFilterProperty'],
        ];
    }

    /**
     * @param array $arProperty
     * @return array<string, mixed>
     */
    public static function PrepareSettings(array $arProperty): array
    {
        $raw = $arProperty['USER_TYPE_SETTINGS'] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }

        $propertyId = (int)($arProperty['ID'] ?? 0);
        $prevFileId = $propertyId > 0 ? self::readBackgroundFileIdFromDb($propertyId) : 0;

        $delBg = !empty($_POST['PROPERTY_USER_TYPE_SETTINGS_del']['BACKGROUND_FILE'])
            && (string)$_POST['PROPERTY_USER_TYPE_SETTINGS_del']['BACKGROUND_FILE'] === 'Y';
            
        $fileId = 0;
        if ($delBg) {
            $fileId = 0;
        } elseif (!empty($raw['BACKGROUND_FILE_del']) && (string)$raw['BACKGROUND_FILE_del'] === 'Y') {
            $fileId = 0;
        } elseif (!empty($raw['BACKGROUND_FILE']) && is_array($raw['BACKGROUND_FILE'])) {
            if (!empty($raw['BACKGROUND_FILE']['del']) && (string)$raw['BACKGROUND_FILE']['del'] === 'Y') {
                $fileId = 0;
            } elseif (!empty($raw['BACKGROUND_FILE']['tmp_name'])) {
                $saved = CFile::SaveFile($raw['BACKGROUND_FILE'], 'iblock');
                $fileId = (int)$saved;
            } else {
                $fileId = (int)($raw['BACKGROUND_FILE']['old_file'] ?? 0);
            }
        } else {
            $fileId = self::parseBackgroundFileIdFromPostValue($raw['BACKGROUND_FILE'] ?? null);
        }

        // CFileInput + upload: при «сохранить» без смены файла в POST нет ID — не затираем уже сохранённый.
        if (!$delBg && $fileId <= 0 && $prevFileId > 0) {
            $fileId = $prevFileId;
        }

        return [
            'BACKGROUND_FILE' => $fileId,
        ];
    }

    /**
     * @param array $arProperty
     * @param array $strHTMLControlName
     * @param array $arPropertyFields
     */
    public static function GetSettingsHTML(array $arProperty, array $strHTMLControlName, array &$arPropertyFields): string
    {
        $arPropertyFields = [
            'HIDE' => [
                'ROW_COUNT',
                'COL_COUNT',
                'MULTIPLE_CNT',
            ],
            'USER_TYPE_SETTINGS_TITLE' => Loc::getMessage('MAKEAPI_UT_IMAGE_HOTSPOTS_SETTINGS_TITLE'),
        ];

        $settings = self::normalizeSettings($arProperty);
        $nameBase = (string)$strHTMLControlName['NAME'];
        $fileId = (string)(int)$settings['BACKGROUND_FILE'];

        $fileHtml = '';
        if (Loader::includeModule('fileman')) {
            ob_start();
            echo CFileInput::Show(
                $nameBase . '[BACKGROUND_FILE]',
                $fileId,
                [
                    'IMAGE' => 'Y',
                    'PATH' => 'Y',
                    'FILE_SIZE' => 'Y',
                    'DIMENSIONS' => 'Y',
                    'IMAGE_POPUP' => 'Y',
                    'MAX_SIZE' => [
                        'W' => 400,
                        'H' => 400,
                    ],
                ],
                [
                    // Только медиабиблиотека: без загрузки с ПК и без диалога «Структура сайта».
                    'upload' => false,
                    'medialib' => true,
                    'file_dialog' => false,
                    'cloud' => false,
                    'del' => true,
                    'description' => false,
                ]
            );
            $fileHtml = (string)ob_get_clean();
        } else {
            $fileHtml = '<input type="text" size="12" name="' . htmlspecialcharsbx($nameBase . '[BACKGROUND_FILE]') . '" value="'
                . htmlspecialcharsbx($fileId) . '">';
        }

        return '
        <tr valign="top">
            <td width="40%">' . Loc::getMessage('MAKEAPI_UT_IMAGE_HOTSPOTS_BG_FILE') . ':</td>
            <td width="60%">' . $fileHtml . '</td>
        </tr>';
    }

    public static function GetAdminListViewHTML(array $arProperty, array $value, array $strHTMLControlName): string
    {
        $pt = self::decodePoint(isset($value['VALUE']) ? (string)$value['VALUE'] : '');
        if ($pt === null) {
            return '&nbsp;';
        }

        return htmlspecialcharsbx(
            Loc::getMessage(
                'MAKEAPI_UT_IMAGE_HOTSPOTS_LIST_COORDS',
                ['#X#' => self::fmtPct($pt['x']), '#Y#' => self::fmtPct($pt['y'])]
            )
        );
    }

    /**
     * @return string|ImageHotspotsValueDto
     */
    public static function GetPublicViewHTML(array $arProperty, array $value, array $strHTMLControlName): string|ImageHotspotsValueDto
    {
        $settings = self::normalizeSettings($arProperty);
        $raw = isset($value['VALUE']) ? (string)$value['VALUE'] : '';
        $pt = self::decodePoint($raw);

        if (($strHTMLControlName['VIEW'] ?? '') === 'JSON') {
            $bg = null;
            $fid = (int)($settings['BACKGROUND_FILE'] ?? 0);
            if ($fid > 0) {
                $bg = FetcherHelper::getFileById($fid);
            }

            $x = $pt !== null ? (float)$pt['x'] : null;
            $y = $pt !== null ? (float)$pt['y'] : null;

            return new ImageHotspotsValueDto($bg, $x, $y);
        }

        if ($pt === null) {
            return '';
        }

        return '<span class="makeapi-image-hotspots-view">'
            . htmlspecialcharsbx(
                Loc::getMessage(
                    'MAKEAPI_UT_IMAGE_HOTSPOTS_PUBLIC_STUB',
                    ['#X#' => self::fmtPct($pt['x']), '#Y#' => self::fmtPct($pt['y'])]
                )
            )
            . '</span>';
    }

    public static function GetPropertyFieldHtml(array $arProperty, array $arValue, array $strHTMLControlName): string
    {
        $baseName = (string)($strHTMLControlName['VALUE'] ?? '');
        $settings = self::normalizeSettings($arProperty);
        $bgFileId = (int)($settings['BACKGROUND_FILE'] ?? 0);

        $json = isset($arValue['VALUE']) && is_string($arValue['VALUE'])
            ? $arValue['VALUE']
            : (is_array($arValue['VALUE'] ?? null) ? '' : (string)($arValue['VALUE'] ?? ''));

        if ($json === '' && is_array($arValue['VALUE'] ?? null)) {
            try {
                $json = json_encode($arValue['VALUE'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException) {
                $json = '';
            }
        }

        $pt = self::decodePoint($json);
        $state = ['v' => self::JSON_DB_VERSION];
        if ($pt !== null) {
            $state['x'] = $pt['x'];
            $state['y'] = $pt['y'];
        }
        $stateJson = '';
        try {
            $stateJson = json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException) {
            $stateJson = '{"v":2}';
        }

        $bgUrl = self::resolveBackgroundPublicUrl($bgFileId);

        $unique = 'p' . (int)($arProperty['ID'] ?? 0) . '_' . md5($baseName);

        if ($bgUrl === '') {
            return '<div class="adm-info-message">'
                . htmlspecialcharsbx(Loc::getMessage('MAKEAPI_UT_IMAGE_HOTSPOTS_NO_BG'))
                . '</div>'
                . '<input type="hidden" name="' . htmlspecialcharsbx($baseName) . '" value="'
                . htmlspecialcharsbx($stateJson) . '">';
        }

        $hint = Loc::getMessage('MAKEAPI_UT_IMAGE_HOTSPOTS_EDITOR_HINT');
        $btnClear = Loc::getMessage('MAKEAPI_UT_IMAGE_HOTSPOTS_BTN_CLEAR_POINT');
        $wrapId = 'makeapi_hs_wrap_' . $unique;
        $hiddenId = 'makeapi_hs_json_' . $unique;

        ob_start();
        ?>
        <div id="<?= htmlspecialcharsbx($wrapId) ?>" class="makeapi-hs-editor" style="max-width:920px">
            <p class="adm-info-message" style="margin:0 0 10px"><?= htmlspecialcharsbx($hint) ?></p>
            <div class="makeapi-hs-stage" style="position:relative;display:inline-block;max-width:100%;border:1px solid #ccd5db;background:#717171;cursor:crosshair;line-height:0">
                <img src="<?= htmlspecialcharsbx($bgUrl) ?>" alt="" style="max-width:100%;height:auto;display:block;user-select:none" class="makeapi-hs-img">
            </div>
            <div style="margin-top:10px">
                <input type="button" class="adm-btn makeapi-hs-clear" value="<?= htmlspecialcharsbx($btnClear) ?>">
            </div>
            <input type="hidden" name="<?= htmlspecialcharsbx($baseName) ?>" id="<?= htmlspecialcharsbx($hiddenId) ?>" value="<?= htmlspecialcharsbx($stateJson) ?>">
        </div>
        <script>
        (function(){
            var wrap = BX('<?= CUtil::JSEscape($wrapId) ?>');
            if(!wrap){return;}
            var stage = wrap.querySelector('.makeapi-hs-stage');
            var img = wrap.querySelector('.makeapi-hs-img');
            var hidden = BX('<?= CUtil::JSEscape($hiddenId) ?>');
            var btnClear = wrap.querySelector('.makeapi-hs-clear');

            function parseState(){
                try { return JSON.parse(hidden.value || '{"v":2}'); }
                catch(e){ return {v:2}; }
            }
            function saveState(st){
                hidden.value = JSON.stringify(st);
            }
            function hasPoint(st){
                return typeof st.x === 'number' && typeof st.y === 'number';
            }
            function renderPin(){
                var st = parseState();
                var pins = stage.querySelectorAll('.makeapi-hs-pin');
                for(var i=0;i<pins.length;i++){pins[i].parentNode.removeChild(pins[i]);}
                if(!hasPoint(st)){return;}
                var pin = document.createElement('div');
                pin.className = 'makeapi-hs-pin';
                pin.title = st.x.toFixed(2)+'%, '+st.y.toFixed(2)+'%';
                pin.style.cssText = 'position:absolute;width:18px;height:18px;margin:-9px 0 0 -9px;border-radius:50%;background:#e64646;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35);left:'+st.x+'%;top:'+st.y+'%';
                stage.appendChild(pin);
            }

            stage.addEventListener('click', function(ev){
                if(ev.target!==img){return;}
                var r = img.getBoundingClientRect();
                if(!r.width||!r.height){return;}
                var x = ((ev.clientX - r.left) / r.width) * 100;
                var y = ((ev.clientY - r.top) / r.height) * 100;
                var st = parseState();
                st.v = <?= (int)self::JSON_DB_VERSION ?>;
                st.x = Math.round(x*1000)/1000;
                st.y = Math.round(y*1000)/1000;
                saveState(st);
                renderPin();
            });

            btnClear.addEventListener('click', function(){
                saveState({v: <?= (int)self::JSON_DB_VERSION ?>});
                renderPin();
            });

            if(img.complete){renderPin();}
            else{img.addEventListener('load', function(){renderPin();});}
            renderPin();
        })();
        </script>
        <?php

        return (string)ob_get_clean();
    }

    public static function ConvertToDB(array $arProperty, array $value): array|false
    {
        $raw = '';
        if (isset($value['VALUE'])) {
            if (is_string($value['VALUE'])) {
                $raw = trim($value['VALUE']);
            } elseif (is_array($value['VALUE'])) {
                try {
                    $raw = trim(json_encode($value['VALUE'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                } catch (\JsonException) {
                    return false;
                }
            }
        }

        if ($raw === '') {
            return false;
        }

        $pt = self::decodePoint($raw);
        if ($pt === null) {
            return false;
        }

        $payload = [
            'v' => self::JSON_DB_VERSION,
            'x' => $pt['x'],
            'y' => $pt['y'],
        ];

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException) {
            return false;
        }

        return [
            'VALUE' => $json,
            'DESCRIPTION' => '',
        ];
    }

    public static function ConvertFromDB(array $arProperty, array $value): array
    {
        $raw = isset($value['VALUE']) ? (string)$value['VALUE'] : '';

        return [
            'VALUE' => $raw,
            'DESCRIPTION' => (string)($value['DESCRIPTION'] ?? ''),
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
        $converted = self::ConvertToDB($arProperty, $value);

        return is_array($converted) && isset($converted['VALUE']) ? mb_strlen((string)$converted['VALUE']) : 0;
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

    /**
     * Настройки в форме элемента приходят из {@see CIBlockElement::GetProperty}: иногда нет
     * распакованного USER_TYPE_SETTINGS — подгружаем из {@see PropertyTable} по ID свойства.
     *
     * @return array{BACKGROUND_FILE: int}
     */
    private static function normalizeSettings(array $arProperty): array
    {
        $propertyId = (int)($arProperty['ID'] ?? 0);

        $s = self::extractUserTypeSettingsArray($arProperty);
        $fileId = (int)($s['BACKGROUND_FILE'] ?? 0);

        if ($fileId <= 0 && $propertyId > 0) {
            $fileId = self::readBackgroundFileIdFromDb($propertyId);
        }

        return ['BACKGROUND_FILE' => $fileId];
    }

    private static function readBackgroundFileIdFromDb(int $propertyId): int
    {
        $row = PropertyTable::getRow([
            'select' => ['USER_TYPE_SETTINGS_LIST'],
            'filter' => ['=ID' => $propertyId],
        ]);
        if ($row === null) {
            return 0;
        }
        $list = $row['USER_TYPE_SETTINGS_LIST'] ?? null;
        if (is_array($list)) {
            return (int)($list['BACKGROUND_FILE'] ?? 0);
        }
        if (is_string($list) && $list !== '') {
            $tmp = @unserialize($list, ['allowed_classes' => false]);
            if (is_array($tmp)) {
                return (int)($tmp['BACKGROUND_FILE'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Значение из POST: число, либо путь /upload/... (медиабиблиотека / вставка пути) — ищем уже существующую запись в b_file.
     *
     * @param mixed $val
     */
    private static function parseBackgroundFileIdFromPostValue($val): int
    {
        if ($val === null || $val === '' || is_array($val)) {
            return 0;
        }
        if (is_numeric($val)) {
            return (int)$val;
        }
        if (!is_string($val)) {
            return 0;
        }
        $val = trim($val);
        if ($val === '') {
            return 0;
        }
        if (ctype_digit($val)) {
            return (int)$val;
        }

        $docRoot = Application::getDocumentRoot();
        $path = str_replace('\\', '/', $val);
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        $full = $docRoot . $path;
        if (!is_file($full)) {
            return 0;
        }

        $norm = ltrim(str_replace('\\', '/', $path), '/');
        $rel = preg_replace('#^upload/#', '', $norm);
        $fileName = basename($rel);
        $subdir = dirname($rel);
        $subdir = ($subdir === '.' || $subdir === '') ? '' : str_replace('\\', '/', $subdir);

        $row = FileTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=FILE_NAME' => $fileName,
                '=SUBDIR' => $subdir,
            ],
            'order' => ['ID' => 'DESC'],
            'limit' => 1,
        ])->fetch();

        return $row ? (int)$row['ID'] : 0;
    }

    private static function resolveBackgroundPublicUrl(int $fileId): string
    {
        if ($fileId <= 0) {
            return '';
        }
        $f = CFile::GetFileArray($fileId);
        if (!is_array($f)) {
            return '';
        }
        $src = (string)CFile::GetFileSRC($f, false, false);
        if ($src !== '') {
            return $src;
        }
        $p = CFile::GetPath($fileId);

        return is_string($p) ? $p : '';
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractUserTypeSettingsArray(array $arProperty): array
    {
        $s = $arProperty['USER_TYPE_SETTINGS_LIST'] ?? $arProperty['USER_TYPE_SETTINGS'] ?? [];
        if (is_string($s) && $s !== '') {
            $tmp = @unserialize($s, ['allowed_classes' => false]);
            $s = is_array($tmp) ? $tmp : [];
        }
        if (!is_array($s)) {
            $s = [];
        }

        return $s;
    }

    /**
     * @return array{x: float, y: float}|null
     */
    private static function decodePoint(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        if (!is_array($data)) {
            return null;
        }

        if (array_key_exists('x', $data) && array_key_exists('y', $data)) {
            return self::clampPoint((float)$data['x'], (float)$data['y']);
        }

        if (isset($data['markers'][0]) && is_array($data['markers'][0])) {
            $m = $data['markers'][0];

            return self::clampPoint((float)($m['x'] ?? 0), (float)($m['y'] ?? 0));
        }

        return null;
    }

    /**
     * @return array{x: float, y: float}
     */
    private static function clampPoint(float $x, float $y): array
    {
        $x = max(0.0, min(100.0, $x));
        $y = max(0.0, min(100.0, $y));

        return ['x' => $x, 'y' => $y];
    }

    private static function fmtPct(float $v): string
    {
        return rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.') . '%';
    }
}
