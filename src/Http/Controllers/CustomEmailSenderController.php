<?php

namespace Dniccum\CustomEmailSender\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Pktharindu\NovaPermissions\Role;
use Spatie\IcalendarGenerator\Components\Event;
use Dniccum\CustomEmailSender\Library\UserUtility;
use Spatie\IcalendarGenerator\Components\Calendar;
use Dniccum\CustomEmailSender\Mail\CustomMessageMailable;
use Dniccum\CustomEmailSender\Library\NebulaSenderUtility;
use Dniccum\CustomEmailSender\Http\Requests\SendCustomEmailMessage;

class CustomEmailSenderController
{
    /**
     * @var UserUtility
     */
    private $userUtility;

    public function __construct()
    {
        $userClassNames = config('novaemailsender.model.classes');

        if (empty($userClassNames)) {
            die('Please define a user class for the Custom Email Sender');
        }

        $this->userUtility = new UserUtility($userClassNames);
    }

    /**
     * Returns the current configuration to be used in the
     * user interface
     *
     * @todo validate the configuration and provide errors if necessary
     * @return \Illuminate\Http\JsonResponse
     */
    public function config()
    {
        $configuration = config('novaemailsender');

        /**
         * @var string[]|array $configurationOptions
         */
        $configurationFromOptions = config('novaemailsender.from.options');

        $fromOptions = collect($configurationFromOptions)->map(function ($sender) {
            return [
                'address' => $sender['address'],
                'name' => $sender['name'] . ' (' . $sender['address'] . ')',
            ];
        });
        if ($user = $this->getAuthUserSender()) {
            $user['name'] = $user['name'] ?  __('Me') . ' (' . $user['name'] . ' | ' . $user['address'] . ')' : '—';
            $fromOptions->push($user);
        }

        $configurationFromOptions = $fromOptions->toArray();
        $configuration['from']['options'] = $configurationFromOptions;

        $nebulaSenderActive = NebulaSenderUtility::isActive();

        return response()
            ->json([
                'config' => $configuration,
                'messages' => array_merge(__('custom-email-sender::tool', [], 'en'), __('custom-email-sender::nebula-sender', [], 'en')),
                'nebula_sender_active' => $nebulaSenderActive,
            ]);
    }

    public function getGroups()
    {
        $return = [];
        $sections = [18, 19, 20, 21];

        $roles = Role::select('id', 'name', 'slug')->with('users:id,first_name,last_name,email')
            ->whereHas('users', function ($query) {
                return $query->where('active', true);
            })->get();

        foreach ($roles as $key => $role) {
            $return[$key] = $role->toArray();
            $return[$key]['users'] = $role->users->pluck('title', 'email');
        }

        foreach ($sections as $key => $sectionId) {
            $users = User::select('id', 'first_name', 'last_name', 'email')->where('active', true)->whereHas('usersActivities', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId)->where('active', true);
            })->get()->pluck('title', 'email');

            switch ($sectionId) {
                case 18:
                    $sectionName = 'Power Section';
                    break;
                case 19:
                    $sectionName = 'Glider Section';
                    break;
                case 20:
                    $sectionName = 'Ultralight Section';
                    break;
                case 21:
                    $sectionName = 'Parachute Section';
                    break;
                default:
                    $sectionName = null;
                    break;
            }

            $return[] = [
                'id' => $sectionId,
                'name' => $sectionName,
                'users' => $users
            ];
        }

        return $return;
    }

    /**
     * Sends the messages to the requested users.
     *
     * @param SendCustomEmailMessage $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(SendCustomEmailMessage $request)
    {
        $requestData = $request->validated();

        $files = $requestData['files'];
        $attachments = [];
        foreach ($files as $key => $file) {
            array_push($attachments, 'email_attachments/' . $file['name']);
        }
        $eventData = $requestData['event'];

        $calendar = null;
        if ($eventData['createEvent']) {
            $event = Event::create($eventData['eventDetails']['eventTitle']);

            if ($eventData['eventDetails']['eventDescription']) $event->description($eventData['eventDetails']['eventDescription']);

            if ($eventData['eventDetails']['eventFullDay']) $event->fullDay();

            else {
                $event->startsAt(\Carbon\Carbon::createFromFormat('d.m.Y H:i', $eventData['eventDetails']['eventDateFrom'], 'Europe/Stockholm'))
                    ->endsAt(\Carbon\Carbon::createFromFormat('d.m.Y H:i', $eventData['eventDetails']['eventDateTo'], 'Europe/Stockholm'));
            }

            $calendar = Calendar::create()
                ->name('eAvio Calendar')
                ->event($event)
                ->get();
        }

        if ($requestData['sendToAll']) {
            $users = $this->userUtility->getAllUsers();
        } else {
            $users = collect($requestData['recipients'])->map(function ($recipient) {
                return [
                    'email' => $recipient['email'],
                ];
            });
        }

        $sender = collect(config('novaemailsender.from.options'))
            ->push($this->getAuthUserSender()) // remember the auth select option
            ->firstWhere('address', $requestData['from']);

        $content = $requestData['htmlContent'];
        $subject = $requestData['subject'];

        $users->each(function ($user) use ($content, $subject, $sender, $attachments, $calendar) {
            \Mail::to($user)
                ->send(new CustomMessageMailable($subject, $content, $sender, $attachments, $calendar));
        });

        if (config('novaemailsender.nebula_sender.key')) {
            $template = config('novaemailsender.template.view');
            $fullContent = (new CustomMessageMailable($subject, $content, $sender))->render();
            NebulaSenderUtility::logSentMessage(
                $requestData['from'],
                $subject,
                $template,
                $users->toArray(),
                $content,
                $fullContent
            );
        }

        return response()->json($users->count() . ' ' . __('custom-email-sender::tool.emails-sent'), 200);
    }

    public function preview(SendCustomEmailMessage $request)
    {
        $requestData = $request->validated();

        $content = $requestData['htmlContent'];
        $subject = $requestData['subject'];

        $email = new CustomMessageMailable($subject, $content);

        return response()->json([
            'content' => $email->render()
        ], 200);
    }

    /**
     * Search results
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('search');
        $results = $this->userUtility->searchUsers($query);

        return response()->json($results, 200);
    }

    /**
     * Get auth user
     *
     * @return array
     */
    private function getAuthUserSender()
    {
        $user = request()->user();

        if ($user) {
            if (config('novaemailsender')) {
                $email = 'email';
                $name = 'first_name';

                if (config('novaemailsender.model.email')) {
                    $email = config('novaemailsender.model.email');
                }
                if (config('novaemailsender.model.name')) {
                    $name = 'name';
                } elseif (config('novaemailsender.model.first_name')) {
                    $name = 'first_name';
                }

                return [
                    'address' => $user->$email ?? null,
                    'name' => $user->$name ?? null
                ];
            }
        }

        return null;
    }
}
