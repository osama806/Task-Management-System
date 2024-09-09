<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TaskService
{
    use ResponseTrait;

    /**
     * Get list of tasks
     * @return array
     */
    public function index(array $data)
    {
        // Initialize the query builder for Task
        $tasks = Task::query();

        // Apply filters if present
        if (isset($data['priority'])) {
            $tasks->priority($data['priority']);
        }
        if (isset($data['status'])) {
            $tasks->status($data['status']);
        }

        // Execute the query and get results
        $tasks = $tasks->get();

        // Check if any tasks were found
        if ($tasks->isEmpty()) {
            return ['status' => false, 'msg' => 'Not Found Any Task!', 'code' => 404];
        }

        // Format the task data for the response
        $taskData = [];
        foreach ($tasks as $task) {
            $taskData[] = [
                'title'             => $task->title,
                'description'       => $task->description,
                'priority'          => $task->priority,
                'assign_to'         => $task->assign_to,
                'status'            => $task->status,
                'due_date'          => $task->due_date,
            ];
        }

        return ['status' => true, 'tasks' => $taskData];
    }

    /**
     * Create new task in storage
     * @param array $data
     * @return array
     */
    public function createTask(array $data)
    {
        $task = Task::create([
            'title'       => $data['title'],
            'description' => $data['description'],
            'priority'    => $data['priority'],
        ]);

        return $task
            ? ['status'    =>  true]
            : ['status'    =>  false, 'msg'    =>  'There is error in server', 'code'  =>  500];
    }

    /**
     * Get spicified task
     * @param \App\Models\Task $task
     * @return array
     */
    public function show(Task $task)
    {
        $data = [
            'title'       => $task->title,
            'description' => $task->description,
            'priority'    => $task->priority,
            'assign_to'   => $task->assign_to,
            'status'      => $task->status,
            'due_date'    => $task->due_date
        ];
        return ['status'    =>  true, 'task'    =>  $data];
    }

    /**
     * Update a spicified task details in storage
     * @param array $data
     * @param \App\Models\Task $task
     * @return array
     */
    public function update(array $data, Task $task)
    {
        if ($task->status !== 'not-started') {
            return ['status'    =>  false, 'msg'    =>  'This Task Is Completly Previous', 'code'   =>  400];
        }
        if (empty($data['title']) && empty($data['description']) && empty($data['priority'])) {
            return ['status' => false, 'msg' => 'Not Found Data in Request!', 'code' => 404];
        }

        if (isset($data['title'])) {
            $task->title = $data['title'];
        }
        if (isset($data['description'])) {
            $task->description = $data['description'];
        }
        if (isset($data['priority'])) {
            $task->priority = $data['priority'];
        }
        $task->save();
        return ['status'    =>  true];
    }

    /**
     * Remove a specified task from storage
     * @param \App\Models\Task $task
     * @return bool[]|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete(Task $task)
    {
        if (!Auth::check() || Auth::user()->role !== "admin") {
            return ['status'    =>  false,  'msg' => "Can't access delete permission", 'code' => 400];
        }
        $task->delete();
        return ['status'    =>  true];
    }

    /**
     * Retrive a spicified task after deleted
     * @param \App\Models\Task $task
     * @return bool[]|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function restore(Task $task)
    {
        if (!Auth::check() || Auth::user()->role !== "admin") {
            return $this->getResponse('error', "Can't access delete permission", 400);
        }
        if ($task->deleted_at === null) {
            return [
                'status' => false,
                'msg' => "This task isn't deleted",
                'code' => 400,
            ];
        }
        $task->restore();

        return ['status' => true];
    }

    /**
     * Deliveried a specified task to admin in specific time
     * @param \App\Models\Task $task
     * @return array
     */
    public function delivery(Task $task)
    {
        if (Auth::user()->role !== null || $task->status !== 'in-progress') {
            return ['status'    =>  false,  'msg' => 'User unAuthorization or task status not in-progress', 'code'  =>   400];
        }
        if ($task->assign_to !== Auth::id()) {
            return ['status'    =>  false,  'msg' => 'This task assigned to another user', 'code'  =>   400];
        }
        $task->status = 'done';
        $task->due_date = now()->toDateTime()->format('d-m-Y H:i');
        $task->save();
        return ['status'    =>  true];
    }

    /**
     * Assigned a specified task to user
     * @param array $data
     * @param \App\Models\Task $task
     * @return array
     */
    public function assign(array $data, Task $task)
    {
        if ($task->assign_to !== null) {
            return ['status' => false, 'msg' => 'This task is already assigned to a user', 'code' => 400];
        }

        $user = User::find($data['assign_to']);
        if (!$user) {
            return ['status' => false, 'msg' => 'User not found!', 'code' => 404];
        }

        if ($user->role !== null) {
            return ['status' => false, 'msg' => "Can't assign task to this user", 'code' => 400];
        }

        try {
            // date with timezone
            $dueDate = Carbon::createFromFormat('d-m-Y H:i', $data['due_date']);
            if ($dueDate->isPast()) {
                return ['status' => false, 'msg' => 'Due date must be a future date.', 'code' => 400];
            }
        } catch (\InvalidArgumentException $e) {
            return ['status' => false, 'msg' => 'Invalid due date format, please use d-m-Y H:i', 'code' => 400];
        }

        $task->assign_to = $data['assign_to'];
        // date without timezone
        $task->due_date = $dueDate->toDateTime()->format('d-m-Y H:i');
        $task->status = 'in-progress';
        $task->save();

        return ['status' => true];
    }
}
