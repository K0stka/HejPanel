<?php
require_once("vendor/autoload.php");

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class NotificationManager {
    private Conn $con;

    public User|bool $user;

    private const TTL = 18000; // Time to live (in seconds) = how long are notifications cached (5 * 3600)

    private array $auth = [];

    public function __construct(User|bool $user) {
        global $con;

        $this->con = $con;

        $this->user = $user;

        $this->auth = self::getAuth();
    }

    public static function getAuth(): array {
        global $prefix;

        return [
            "VAPID" => [
                'subject' => $prefix,
                'publicKey' => PUBLIC_KEY,
                'privateKey' => PRIVATE_KEY,
            ]
        ];
    }

    public function sendNotification(string $title, string $body, string $url = null) {
        $this->sendNotificationToUser($this->user, $title, $body, $url);
    }

    public static function broadcastNotification(callable $titleBodyUrlCallback) {
        $webPush = new WebPush(self::getAuth());

        foreach (self::getAllSubscriptions() as $subscription) {
            [$title, $body, $url] = $titleBodyUrlCallback($subscription["user"]);
            self::sendNotificationToSubscription($webPush, $subscription["subscription"], $title, $body, $url);
        }
    }

    public static function sendNotificationToUser(User $user, string $title, string $body, string $url = null) {
        global $prefix;
        if (!$url) $url = $prefix;

        $webPush = new WebPush(self::getAuth());

        /** @var Subscription[] $subscriptions */
        $subscriptions = self::getSubscriptions($user);
        foreach ($subscriptions as $subscription) {
            self::sendNotificationToSubscription($webPush, $subscription, $title, $body, $url);
        }
    }

    public static function sendNotificationToSubscription(WebPush $webPush, Subscription $subscription, string $title, string $body, string $url) {
        $webPush->sendOneNotification(
            $subscription,
            utf8json(["title" => $title, "body" => $body, "url" => $url]),
            ['TTL' => self::TTL]
        );
    }

    private static function getAllSubscriptions(): array {
        global $con;
        $subscriptions = $con->select(["subscription", User::SESSION_KEY], "sessions")->addSQL("WHERE subscription != NULL")->groupBy("subscription")->fetchAll();

        if (empty($subscriptions)) return [];

        return array_map(fn ($e) => [
            "user" => new User($e[User::SESSION_KEY], true),
            "subscription" => Subscription::create(json_decode($e["subscription"], true), ["ttl" => self::TTL])
        ], $subscriptions);
    }

    private static function getSubscriptions(User $user): array {
        global $con;
        $existingSubscriptions = $con->select("subscription", "sessions")->where([User::SESSION_KEY => $user->id])->addSQL("AND subscription != NULL")->fetchAll();

        if (empty($existingSubscriptions)) return [];

        return array_map(fn ($e) => Subscription::create(json_decode($e["subscription"], true), ["ttl" => self::TTL]), $existingSubscriptions);
    }
}
