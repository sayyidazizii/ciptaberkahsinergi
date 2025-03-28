<?php

use App\Models\CoreAnouncement;
use App\Models\MobileUser;
use NotificationChannels\Fcm\FcmMessage;
use App\Notifications\MobileAnouncement;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

test('Sending notification', function () {
    if(CoreAnouncement::active()->count() == 0){
            CoreAnouncement::factory(2)->create();
    }
        $anouncement = CoreAnouncement::active()->first();
        $user = MobileUser::find(2);
        $user->notify(new MobileAnouncement($anouncement));


        // $users = MobileUser::all();
        // Notification::send($users, new MobileAnouncement($anouncement));

        $this->assertTrue(true);
});
