<?php

use App\Models\MobileUser;
use App\Notifications\GlobalAnouncement;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

test('Sending notification', function () {
    $n = (new FcmMessage(notification: new FcmNotification(
        title: 'Account Activated',
        body: 'Your account has been activated.',
        image: 'https://picsum.photos/200/300'
    )))
    ->data(['data1' => 'value', 'data2' => 'value2'])
    ->custom([
        'android' => [
            'notification' => [
                'color' => '#0A0A0A',
            ],
            'fcm_options' => [
                'analytics_label' => 'analytics',
            ],
        ]
        ]);
        fwrite(STDOUT,print_r($n));

        $this->assertTrue(true);
});
