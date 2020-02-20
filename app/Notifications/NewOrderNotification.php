<?php

namespace App\Notifications;

use App\Models\PerAccountsModels\MongoModels\EventNotification;
use App\Models\PerAccountsModels\MongoModels\Order;
use App\Models\User;
use App\Repositories\PerAccountsRepositories\Contracts\EventNotificationRepositoryInterface;
use App\Repositories\PerAccountsRepositories\EventNotificationRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;

class NewOrderNotification extends Notification implements ShouldQueue
{
     use Queueable;

    /**
     * @var Order
     */
    protected $order;


    /**
     * @var User
     */
    protected $user;

    /**
     * @var EventNotification
     */
    protected $notification;


    /**
     * @var EventNotificationRepository
     */
    protected $eventNotificationRepository;

    /**
     * NewOrderNotification constructor.
     * @param Order $order
     * @param $user
     */
    public function __construct(Order $order, $user)
    {
        $this->eventNotificationRepository = resolve('App\Repositories\PerAccountsRepositories\EventNotificationRepository');
        $this->order = $order;
        $this->user = $user;
    }

    /**
     * @param $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $array = [];
        /**
         * @var $notifiable
         */

        if ($notifiable->email) {
            $array[] = 'mail';
        }
        if ($notifiable->fcm_token) {
            $array[] = FcmChannel::class;
        }

        return $array;
    }

    /**
     * @param $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('email.new_order_subj'). ' ' . $this->order->order_num)
            ->markdown('emails.orders.created', [
                'orderNum'  => $this->order->order_num,
                'orderId'   => $this->order->_id,
                'account'   => $this->user->account,
                'shop'      => $this->order->shop->name
            ]);
    }

    public function toFcm($notifiable)
    {
        $data = [
            'title' => __('email.new_order'). ' ' . $this->order->order_num,
            "body" =>  __('email.new_order_number'). ' ' . $this->order->order_num,
            'order_id' => $this->order->_id,
            'type' => EventNotificationRepositoryInterface::EVENT_NEW_ORDER,
        ];

        // The FcmMessage contains other options for the notification
        return FcmMessage::create()
            ->setPriority(FcmMessage::PRIORITY_HIGH)
            ->setTimeToLive(86400)
            ->setData($data);
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
