<?php

namespace App\Http\Controllers;

use App\User;
use App\Task;
use App\Reward;
use App\KeyPool;
use App\Http\Traits\ApiTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyController extends Controller
{
    use ApiTrait;

    /**
     * set guard to replace auth()
     */
    public function guard()
    {
        return Auth::guard('api');
    }

    /**
     * @param Request $request
     * @param string $vType
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request, string $vType)
    {
        if (! $request->filled(['uid', 'vKey'])) {
            return $this->return400Response();
        }

        if (! $this->checkKey($request->input('vKey'), $vType)) {
            return $this->return400Response();
        }

        $uid = $request->input('uid');
        $user = $this->guard()->user();
        $achievement = $user->achievement;


        switch ($vType) {
            case KeyPool::TYPE_TASK:
                $task = Task::where('uid', $uid)->firstOrFail();
                $task_id = $task->id;
                $taskCollection = collect($achievement[User::COMPLETED_TASK]);
                if($taskCollection->where(['task_id', $task_id])->isEmpty()){
                    $score = $user
                        ->scores()
                        ->get()
                        ->firstWhere('task_id', $task->id);
                    if ($score) {
                        if($score->pass == 0){
                            $score->pass = 1;
                            $score->save();

                            array_push(
                                $achievement[User::COMPLETED_TASK], 
                                [
                                    'mission_id' => $score->mission_id,
                                    'task_id' => $score->task_id,
                                ]
                            );
                            $achievement[User::WON_POINT] += $score->point;
                        }
                    } else {
                        return $this->return400Response();
                    }
                }

                break;

            case KeyPool::TYPE_REWARD:
                $reward = Reward::where('uid', $uid)->firstOrFail();
                if(! $reward->redeemable){
                    return $this->return400Response();
                }

                $reward_id = $reward->id;
                $rewardCollection = collect($achievement[User::WON_REWARD]);
                $newCollection = null;

                if ($rewardCollection->where('reward_id', $reward_id)->isNotEmpty()) {
                    $newCollection = $rewardCollection->map(
                        function ($item) use ($reward_id) {
                            if ($item['reward_id'] == $reward_id) {
                                $item['redeemed'] = true;
                            }

                            return $item;
                        }
                    );
                } else {
                    return $this->return400Response();
                }

                if ($newCollection) {
                    $achievement[User::WON_REWARD] = $newCollection->all();
                }

                break;

        }

        $user->achievement = $achievement;
        if ($user->isDirty('achievement')) {
            $user->save();
        }

        return $this->returnSuccess('Success.', $user);
    }


    /**
     * @param string $key
     * @param string $type
     * @return boolean
     */
    private function checkKey(string $key, string $type)
    {
        return KeyPool::where([
            ['key', $key],
            ['type', $type]
        ])->exists();
    }
}
