# Версия 1.0.0

Новое:
- добавлен метод /forms
- добавлен метод /search
- добавлен метод /page
- добавлен метод /menu
- добавлен метод /meta
- добавлен метод /iblock/{iblock_code}/sections
- добавлен метод /iblock/{iblock_code}/section
- добавлен метод /iblock/{iblock_code}/tags
- добавлен метод /documentation (OpenAPI JSON)
- добавлен метод /version
- подключен валидатор через composer (marwanalsoltany/mighty)
- добавлен параметр properties для /iblock/elements
- автогенерация свагера через аннотации (zircote/swagger-php)
- добавлены пользовательские типы свойств BlockContent и ImageHotspots
- добавлена установка HL-блока Meta
- добавлен обработчик 404 для неизвестных маршрутов API
- добавлены Fetcher-сервисы для файлов и изображений


Изменения:
- рефакторинг билдера (QueryBuilder, ORM-конвертеры)
- изменена структура Dto (Entity/Request/Response/Filter/UserType)
- IblockService вынесен в namespace Service\Iblock, добавлен IblockSectionService
- HL-блок Content перенесён в install/hlblock/Content.php
- расширена иерархия HTTP-исключений