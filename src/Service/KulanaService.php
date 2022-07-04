<?php

namespace Drupal\kulana\Service;

use DateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use GuzzleHttp\Client;

class KulanaService {

  public function fetchStatus(string $url) {
    $client = new Client();
    $status = $client->get("https://kulana.ohano.me/status?url=$url&checkCertificate=1")->getBody()->getContents();

    if ($status == NULL) {
      return NULL;
    }

    \Drupal::messenger()->addMessage($status);

    return json_decode($status, TRUE);
  }

  public function saveStatus(string $url, int $status, float $time, string $destination, int $contentLength, bool $certificateValid, DateTime $certificateValidUntil, string $certificateIssuer, bool $sslRedirect) {
    $statusEntry = \Drupal::entityTypeManager()->getStorage('status_entry')->create([
      'url' => $url,
      'status' => $status,
      'time' => $time,
      'destination' => $destination,
      'content_length' => $contentLength,
      'certificate_valid' => $certificateValid,
      'certificate_valid_until' => $certificateValidUntil->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      'certificate_issuer' => $certificateIssuer,
      'ssl_redirect' => $sslRedirect,
    ]);
    $statusEntry->save();
    return $statusEntry;
  }

  public function execute(Node $host) {
    $url = $host->get('field_url')->uri;
    $entityQuery = \Drupal::entityQuery('status_entry')
      ->condition('url', $url)
      ->sort('time', 'DESC')
      ->range(0, 1);
    $lastFetched = $entityQuery->execute();
    if (!empty($lastFetched)) {
      $lastFetchedEntity = \Drupal::entityTypeManager()
        ->getStorage('status_entry')
        ->load(array_values($lastFetched)[0]);
      $lastFetchedCreated = (int) $lastFetchedEntity->get('created')->value;
      $now = (new \DateTime())->getTimestamp();
      $interval = (int) $host->get('field_interval')->value * 60;
      if ($now - $lastFetchedCreated < $interval) {
        return;
      }
    }

    /** @noinspection HttpUrlsUsage */
    $unsafeUrl = str_replace('https://', 'http://', $url);

    $status = $this->fetchStatus($url);
    $sslRedirectStatus = $this->fetchStatus($unsafeUrl);
    if ($status == NULL) {
      \Drupal::logger('kulana')->error("Failed to fetch status for $url");
      return;
    }

    $sslRedirect = FALSE;
    if ($sslRedirectStatus['status'] >= 300 && $sslRedirectStatus['status'] < 400 && str_contains($sslRedirectStatus['destination'], 'https://')) {
      $sslRedirect = TRUE;
    }

    $this->saveStatus(
      $status['url'],
      $status['status'],
      $status['time'],
      $status['destination'] ?? $status['url'],
      $status['content_length'] ?? 0,
      $status['certificate']['valid'],
      DateTime::createFromFormat('Y-m-d H:i:s', $status['certificate']['valid_until']),
      $status['certificate']['issuer'],
      $sslRedirect
    );
  }

  public function executeAll() {
    $fiber = new \Fiber(function () {
      $allHostIds = \Drupal::entityQuery("node")
        ->condition("type", "kulana_status_host")
        ->execute();
      foreach ($allHostIds as $hostId) {
        /** @var Node $host */
        $host = \Drupal::entityTypeManager()->getStorage("node")->load($hostId);
        $this->execute($host);
      }
    });
    try {
      $fiber->start();
    } catch (\Throwable $e) {
      \Drupal::logger('kulana')->error($e->getMessage());
    }
  }
}
