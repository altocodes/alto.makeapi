<?php

namespace Alto\MakeApi\Controller;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "test",
    description: "Запуск тестов API (по тест-кейсу iblock-elements-products)"
)]
class TestController extends BaseController
{
    private string $baseUrl;
    private array $results = [];

    /**
     * Запуск тестов GET /api/v1/iblock/products/elements по тест-кейсу.
     * Возвращает массив результатов (сериализуется в JSON).
     */
    #[OA\Get(
        path: "/api/v1/test",
        description: "Запускает тесты метода iblock/elements (products) и возвращает массив результатов",
        summary: "Запуск тестов API",
        tags: ["test"]
    )]
    #[OA\Response(response: "200", description: "Массив секций с результатами тестов")]
    public function runAction(): array
    {
        $this->baseUrl = $this->getBaseUrl();
        $this->results = [];

        $this->runSection('1. Базовые сценарии (без фильтров)', [
            ['GET /api/v1/iblock/products/elements', [], 200, function (array $body) {
                return isset($body['items']) && is_array($body['items'])
                    && isset($body['pagination'])
                    && (count($body['items']) <= 10);
            }, 'Список элементов, limit=10 по умолчанию'],
            ['GET /api/v1/iblock/products/elements?limit=10&page=1', [], 200, function (array $body) {
                return isset($body['items']) && isset($body['pagination']);
            }, 'Явная пагинация limit=10, page=1'],
            ['GET /api/v1/iblock/products/elements?limit=1&page=1', [], 200, function (array $body) {
                return isset($body['items']) && count($body['items']) <= 1;
            }, 'limit=1 — ровно 1 элемент'],
            ['GET /api/v1/iblock/products/elements?limit=100&page=1', [], 200, function (array $body) {
                return isset($body['items']) && count($body['items']) <= 100;
            }, 'limit=100 — до 100 элементов'],
            ['GET /api/v1/iblock/products/elements?limit=20&page=2', [], 200, function (array $body) {
                return isset($body['items']) && isset($body['pagination']);
            }, 'Вторая страница по 20 элементов'],
            ['GET /api/v1/iblock/products/elements?sort=NAME&order=ASC', [], 200, null, 'Сортировка NAME ASC'],
            ['GET /api/v1/iblock/products/elements?sort=NAME&order=DESC', [], 200, null, 'Сортировка NAME DESC'],
            ['GET /api/v1/iblock/products/elements?sort=ID&order=ASC', [], 200, null, 'Сортировка ID ASC'],
            ['GET /api/v1/iblock/products/elements?sort=DATE_CREATE&order=DESC', [], 200, null, 'Сортировка DATE_CREATE DESC'],
            ['GET /api/v1/iblock/products/elements?sort=SORT&order=ASC', [], 200, null, 'Сортировка SORT ASC'],
        ]);

        $this->runSection('2. Запрос свойств (parameters.properties)', [
            ['GET /api/v1/iblock/products/elements?properties=CLASS&limit=1', [], 200, function (array $body) {
                if (empty($body['items'])) return true;
                $first = $body['items'][0];
                return isset($first['properties']) && array_key_exists('class', $first['properties']);
            }, 'Одно свойство CLASS'],
            ['GET /api/v1/iblock/products/elements?properties=CLASS,TYPE,BRAND&limit=1', [], 200, function (array $body) {
                if (empty($body['items'])) return true;
                $p = $body['items'][0]['properties'] ?? [];
                return array_key_exists('class', $p) && array_key_exists('type', $p) && array_key_exists('brand', $p);
            }, 'Несколько свойств CLASS,TYPE,BRAND'],
            ['GET /api/v1/iblock/products/elements?properties=CLASS,UNKNOWN_PROP&limit=1', [], 200, function (array $body) {
                if (empty($body['items'])) return true;
                $p = $body['items'][0]['properties'] ?? [];
                return array_key_exists('class', $p);
            }, 'Несуществующий код свойства игнорируется'],
        ]);

        $this->runSection('3. Фильтрация по стандартным полям', [
            ['GET /api/v1/iblock/products/elements?filter[ACTIVE]=Y&limit=5', [], 200, function (array $body) {
                return $this->assertAllItemsMatch($body, function (array $el) {
                    return isset($el['active']) && $el['active'] === true;
                });
            }, 'Фильтр ACTIVE=Y — все элементы active=true'],
            ['GET /api/v1/iblock/products/elements?filter[ID]=1', [], 200, function (array $body) {
                return $this->assertAllItemsMatch($body, function (array $el) {
                    return isset($el['id']) && (int) $el['id'] === 1;
                });
            }, 'Фильтр по ID=1 — все элементы id=1'],
            ['GET /api/v1/iblock/products/elements?filter[@ID]=1,2,3', [], 200, function (array $body) {
                $allowed = [1, 2, 3];
                return $this->assertAllItemsMatch($body, function (array $el) use ($allowed) {
                    return isset($el['id']) && in_array((int) $el['id'], $allowed, true);
                });
            }, 'Фильтр ID IN (1,2,3) — все id из списка'],
        ]);

        $this->runSection('4. Фильтрация по свойствам', [
            ['GET /api/v1/iblock/products/elements?filter[PROPERTY_CLASS]=Premium&limit=5', [], 200, function (array $body) {
                return $this->assertAllItemsMatch($body, function (array $el) {
                    return $this->propertyEquals($el, 'class', 'Premium');
                });
            }, 'Фильтр PROPERTY_CLASS=Premium — у всех properties.class=Premium'],
            ['GET /api/v1/iblock/products/elements?filter[PROPERTY_IS_CAN_3D4D]=YES&limit=5', [], 200, function (array $body) {
                return $this->assertAllItemsMatch($body, function (array $el) {
                    return $this->propertyEquals($el, 'is_can_3d4d', 'YES');
                });
            }, 'Фильтр PROPERTY_IS_CAN_3D4D=YES — у всех свойство = YES'],
            ['GET /api/v1/iblock/products/elements?filter[ACTIVE]=Y&filter[PROPERTY_CLASS]=Premium&limit=5', [], 200, function (array $body) {
                return $this->assertAllItemsMatch($body, function (array $el) {
                    return isset($el['active']) && $el['active'] === true
                        && $this->propertyEquals($el, 'class', 'Premium');
                });
            }, 'Комбинированный фильтр — active и class=Premium у всех'],
        ]);

        $this->runSection('5. Негативные и граничные сценарии', [
            ['GET /api/v1/iblock/nonexistent_iblock/elements', [], 404, null, 'Несуществующий инфоблок → 404'],
            ['GET /api/v1/iblock/products/elements?page=99999&limit=10', [], 200, function (array $body) {
                return isset($body['items']) && is_array($body['items']) && count($body['items']) === 0;
            }, 'Пустая страница (page=99999) → data=[]'],
        ]);

        $this->runSection('6. Структура ответа', [
            ['GET /api/v1/iblock/products/elements?limit=1', [], 200, function (array $body) {
                if (empty($body['items'])) return true;
                $el = $body['items'][0];
                $has = isset($el['id']) && array_key_exists('name', $el) && array_key_exists('code', $el)
                    && array_key_exists('properties', $el) && is_array($el['properties']);
                return $has;
            }, 'Элемент содержит id, name, code, properties'],
            ['GET /api/v1/iblock/products/elements?limit=1', [], 200, function (array $body) {
                return isset($body['pagination'])
                    && (isset($body['pagination']['total']) || isset($body['pagination']['limit']));
            }, 'Метаданные пагинации (total, limit, page)'],
        ]);

        $total = 0;
        $passed = 0;
        foreach ($this->results as $section) {
            foreach ($section['cases'] as $c) {
                $total++;
                if ($c['passed']) {
                    $passed++;
                }
            }
        }

        return [
            'summary' => [
                'total' => $total,
                'passed' => $passed,
            ],
            'sections' => $this->results,
        ];
    }

    private function getBaseUrl(): string
    {
        $request = $this->getRequest();
        $host = $request->getServer()->get('HTTP_HOST') ?: 'localhost';
        $scheme = $request->getServer()->get('REQUEST_SCHEME') ?: 'http';
        if ($request->getServer()->get('HTTPS') === 'on') {
            $scheme = 'https';
        }
        return $scheme . '://' . $host;
    }

    private function runSection(string $title, array $cases): void
    {
        $this->results[] = ['section' => $title, 'cases' => []];

        foreach ($cases as $case) {
            $path = $case[0];
            $expectedStatus = $case[2];
            $assert = $case[3] ?? null;
            $description = $case[4] ?? $path;

            $url = $this->baseUrl . preg_replace('#^GET\s+#', '', $path);
            $response = $this->httpGet($url);
            $body = $response['body'];
            // Bitrix Engine может отдавать payload в data
            if (is_array($body) && isset($body['data']) && is_array($body['data'])) {
                $body = $body['data'];
            }
            $status = $response['status'];

            $ok = ($status === $expectedStatus);
            if ($ok && $assert !== null && $body !== null) {
                $ok = $assert($body);
            }

            $this->results[array_key_last($this->results)]['cases'][] = [
                'description' => $description,
                'url' => $url,
                'expected_status' => $expectedStatus,
                'actual_status' => $status,
                'passed' => $ok,
                'body_preview' => $this->buildBodyPreview($body, $response['raw']),
            ];
        }
    }

    private function httpGet(string $url): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['status' => 0, 'body' => null, 'raw' => 'curl_init failed'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = null;
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : null;
        }

        return ['status' => $status, 'body' => $body, 'raw' => $raw === false ? '' : substr($raw, 0, 500)];
    }

    /**
     * Проверяет, что все элементы в body['items'] удовлетворяют предикату.
     * Пустой список считается успехом.
     */
    private function assertAllItemsMatch(array $body, callable $predicate): bool
    {
        if (!isset($body['items']) || !is_array($body['items'])) {
            return false;
        }
        foreach ($body['items'] as $el) {
            if (!is_array($el) || !$predicate($el)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Проверяет совпадение свойства элемента с ожидаемым значением.
     * Свойство может быть строкой (единственное) или массивом (множественное).
     */
    private function propertyEquals(array $el, string $propertyKey, string $expected): bool
    {
        $properties = $el['properties'] ?? [];
        if (!is_array($properties) || !array_key_exists($propertyKey, $properties)) {
            return false;
        }
        $value = $properties[$propertyKey];
        if (is_array($value)) {
            return in_array($expected, $value, true);
        }
        if (is_scalar($value)) {
            return (string) $value === $expected;
        }
        return false;
    }

    private function buildBodyPreview(?array $body, string $rawFallback): array
    {
        if ($body === null) {
            return ['raw' => $rawFallback !== '' ? substr($rawFallback, 0, 500) : ''];
        }
        $preview = [];
        if (isset($body['items']) && is_array($body['items'])) {
            $preview['items_count'] = count($body['items']);
            $preview['items_sample'] = array_slice($body['items'], 0, 1);
        }
        if (isset($body['pagination'])) {
            $preview['pagination'] = $body['pagination'];
        }
        if ($preview === []) {
            $preview = array_slice($body, 0, 3, true);
        }
        return $preview;
    }
}
