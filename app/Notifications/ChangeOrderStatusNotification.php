<?php

namespace App\Notifications;

use App\Models\PerAccountsModels\MongoModels\Customer;
use App\Models\PerAccountsModels\MongoModels\EventNotification;
use App\Models\PerAccountsModels\MongoModels\Order;
use App\Repositories\PerAccountsRepositories\AccountSettingsRepository;
use App\Repositories\PerAccountsRepositories\Contracts\AccountSettingsRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\EventNotificationRepositoryInterface;
use App\Repositories\PerAccountsRepositories\EventNotificationRepository;
use App\Services\Notifications\TurboSmsServiceConfig;
use App\Services\Notifications\UniSenderServiceConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Channels\SmsChannel;
use App\Channels\UniSenderMailChannel;
use App\Services\Notifications\UniSenderService;
use App\Services\Notifications\TurboSmsService;

class ChangeOrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var EventNotification
     */
    protected $notification;


    /**
     * @var AccountSettingsRepository
     */
    protected $accountSettingsRepository;


    /**
     * @var EventNotificationRepository
     */
    protected $eventNotificationRepository;

    /**
     * ChangeOrderStatusNotification constructor.
     * @param EventNotification $notification
     * @param Order $order
     */
    public function __construct(EventNotification $notification, Order $order)
    {
        $this->accountSettingsRepository = resolve('App\Repositories\PerAccountsRepositories\AccountSettingsRepository');
        $this->eventNotificationRepository = resolve('App\Repositories\PerAccountsRepositories\EventNotificationRepository');
        $this->notification = $notification;
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  Customer  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $array = [];
        /**
         * @var $notifiable
         */
        if ($this->notification->type == EventNotificationRepositoryInterface::TYPE_MAIL && $notifiable->email) {
            $array[] = UniSenderMailChannel::class;
        }
        if ($this->notification->type == EventNotificationRepositoryInterface::TYPE_SMS) {
            $array[] = SmsChannel::class;
        }

        return $array;
    }

    /**
     * @param Customer $notifiable
     */
    public function toUniSenderMail($notifiable)
    {
        $settings = $this->accountSettingsRepository->getByType(AccountSettingsRepositoryInterface::UNISENDER);

        $config = new UniSenderServiceConfig();
        $config->subject = $this->notification->subject;
        $config->token = $settings->value['api_key'];
        $config->sender_name = $settings->value['sender_name'];
        $config->sender_email = $settings->value['sender_email'];
        $config->list_id = $settings->value['list_id'];

        $mail = new UniSenderService($config);
        $replace = ['order_num' => $this->order->order_num, 'account_name'  => \Auth::user()->full_name];
        $message = $this->eventNotificationRepository->str_replace_dynamic($replace, $this->notification->message);
        $mail->sendMail($notifiable->email, $message);

    }

    /**
     * @param Customer $notifiable
     */
    public function toSms($notifiable)
    {
        $settings = $this->accountSettingsRepository->getByType(AccountSettingsRepositoryInterface::TURBO_SMS);

        $config = new TurboSmsServiceConfig();
        $config->login = $settings->value['login'];
        $config->password = $settings->value['password'];
        $config->sender = $settings->value['sender'];

        $sms = new TurboSmsService($config);
        $replace = ['order_num' => $this->order->order_num, 'account_name'  => \Auth::user()->full_name];
        $message = $this->eventNotificationRepository->str_replace_dynamic($replace, $this->notification->message);
        $sms->send($notifiable->phone, $message);

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
