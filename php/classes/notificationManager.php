<?php
require_once("vendor/autoload.php");

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class NotificationManager {
    private Conn $con;

    public User|bool $user;

    private const PRIVATE_KEY = "-aK_bQIdodRFksLDQpjI1kQ6CFxsz6eRhwe_BlYZ4U8";
    public const PUBLIC_KEY = "BFVnlnDaWQhSnO80A2O8XJGAyfzPUUZ4e_ZwyglewR_-tiI5eVoOsGWPvP1_7C1hjlrvpd3DLB-APlJrQvL5HjY";

    private int $ttl = 18000; // Time to live (in seconds) = how long are notifications cached (5 * 3600)

    private array $auth = [];

    public function __construct(User|bool $user) {
        global $prefix, $con;

        $this->con = $con;

        $this->user = $user;

        $this->auth["VAPID"] = [
            'subject' => $prefix,
            'publicKey' => self::PUBLIC_KEY,
            'privateKey' => self::PRIVATE_KEY,
        ];
    }

    public function sendNotification(string $title, string $body, string $url = null) {
        global $prefix;
        if (!$url) $url = $prefix;

        /** @var Subscription[] $subscriptions */
        $webPush = new WebPush($this->auth);

        $subscriptions = $this->getSubscriptions();
        foreach ($subscriptions as $subscription) {
            $webPush->sendOneNotification(
                $subscription,
                utf8json(["title" => $title, "body" => $body, "url" => $url]),
                ['TTL' => $this->ttl]
            );
        }
    }

    private function getSubscriptions(): array {
        if (!$this->user) return [];

        $existingSubscriptions = $this->con->query("SELECT subscription FROM sessions WHERE user_id = ", [$this->user->id], "AND subscription != NULL")->fetchAll();

        if (empty($existingSubscriptions)) {
            return [];
        } else {
            return array_map(fn ($e) => Subscription::create(json_decode($e["subscription"], true), ["ttl" => $this->ttl]), $existingSubscriptions);
        }
    }
}
