# Renyu-CURL 🌐

Renyu-CURL은 PHP에서 쉽고 효율적으로 CURL 요청을 구현할 수 있게 해주는 라이브러리입니다! 

간편한 인터페이스를 통해 HTTP 요청을 손쉽게 보내고, Cloudflare를 사용하는 사이트와의 통신을 최적화하여 더 빠른 데이터 통신이 가능해요 🤗

## ✨ 주요 기능

- **쉬운 CURL 요청:** 직관적인 메서드를 통해 CURL 요청을 간단하고 명확하게 구성할 수 있어요 👍
- **Cloudflare 간 통신 최적화:** Cloudflare를 사용하는 서버와의 통신을 자동으로 감지하고 Cloudflare Enterprise 노드와 강제로 연결하여 지연속도를 줄여요 (한국의 경우 ICN 리전으로 연결) ⚡
- **사용자 정의 가능:** 요청 헤더, 사용자 에이전트 등 다양한 CURL 옵션을 사용자가 직접 쉽게 설정할 수 있어요 🛠

## 🛠️ 사용 방법

Renyu-CURL을 사용하는 것은 매우 간단합니다. 기본적인 사용 예제는 다음과 같아요

```php
require 'Renyu-CURL.php';

use LIB\CURL;

// CURL 인스턴스 생성
$curl = new CURL('https://example.com/api/data', 'GET');

// 필요한 경우 페이로드 설정
$curl->PAYLOAD(json_encode(['key' => 'value']));

// 요청 헤더 설정
$curl->HEADER([
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer your_token_here'
]);

// 요청 전송 및 응답 받기
$response = $curl->SEND();

// 응답 출력
echo $response['RESPONSE']['DATA'];
