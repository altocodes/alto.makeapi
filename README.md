# Установка и настройка

В случае c nginx, для подключения маршрутов, необходимо изменить секцию @bitrix, на

```editorconfig
location @bitrix {
    fastcgi_pass $php_sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $realpath_root/bitrix/routing_index.php;
    fastcgi_param DOCUMENT_ROOT $realpath_root;
}
```

Для apache2 нужно заменить в .htaccess
```apacheconf
#RewriteCond %{REQUEST_FILENAME} !/bitrix/urlrewrite.php$
#RewriteRule ^(.*)$ /bitrix/urlrewrite.php [L]
RewriteCond %{REQUEST_FILENAME} !/bitrix/routing_index.php$
RewriteRule ^(.*)$ /bitrix/routing_index.php [L]
```


Так же нужно изменить конфиг /bitrix/.settings.php, добавив настройки роутинга
```php
'routing' => [
    'value' => [
        'config' => [
            'makeapi.php'
        ]
    ]
]
```

Скопировать папку и файлы в /local/
```angular2html
TODO: автоматизировать
./swagger
./composer.json
./composer.lock
```

Установить пакеты из composer

# Ограничения использования

- Свойства в инфоблоке следует называть отличным от стандартных полей (например ID, NAME, CODE и т.п.), иначе выборка будет некорректная
- у инфоблока обязательно должно быть заполнено поле "Символьный код API"
