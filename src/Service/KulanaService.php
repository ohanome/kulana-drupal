<?php

namespace Drupal\kulana\Service;

use Drupal\node\Entity\Node;

class KulanaService {
  public function isInstalled(): bool {
    $returnCode = shell_exec("which kulana");
    if ($returnCode == NULL) {
      \Drupal::messenger()->addError(t('Kulana is not installed. Please install it first. Refer to <a href="@url">Kulana installation instructions</a>.', ['@url' => 'https://github.com/ohanome/kulana']));
      return FALSE;
    }

    return TRUE;
  }

  public function fetchStatus(string $url) {
    if (!$this->isInstalled()) {
      return NULL;
    }

    $status = shell_exec("kulana status --url $url --include destination --include content_length --json");
    if ($status == NULL) {
      return NULL;
    }

    \Drupal::messenger()->addMessage($status);

    return json_decode($status, TRUE);
  }

  public function saveStatus(string $url, int $status, float $time, string $destination, int $contentLength) {
    $statusEntry = \Drupal::entityTypeManager()->getStorage('status_entry')->create([
      'url' => $url,
      'status' => $status,
      'time' => $time,
      'destination' => $destination,
      'content_length' => $contentLength,
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

    $status = $this->fetchStatus($url);
    if ($status == NULL) {
      \Drupal::logger('kulana')->error("Failed to fetch status for $url");
      return;
    }
    $this->saveStatus($status['url'], $status['status'], $status['time'], $status['destination'] ?? $status['url'], $status['content_length'] ?? 0);
  }

  public function executeAll() {
    $allHostIds = \Drupal::entityQuery("node")
      ->condition("type", "kulana_status_host")
      ->execute();
    foreach ($allHostIds as $hostId) {
      /** @var Node $host */
      $host = \Drupal::entityTypeManager()->getStorage("node")->load($hostId);
      $this->execute($host);
    }
  }
}
