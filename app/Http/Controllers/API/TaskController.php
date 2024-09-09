<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\AssignFormRequest;
use App\Http\Requests\Task\FilterFormRequest;
use App\Http\Requests\Task\StoreFormRequest;
use App\Http\Requests\Task\UpdateFormRequest;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ResponseTrait;

class TaskController extends Controller
{
    use ResponseTrait;

    protected $taskService;
    public function __construct(TaskService $taskService)
    {
        $this->taskService  = $taskService;
    }

    /**
     * Display a listing of the tasks.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function index(FilterFormRequest $filterFormRequest)
    {
        $validated = $filterFormRequest->validated();
        $response = $this->taskService->index($validated);
        return $response['status']
            ? $this->getResponse('tasks', $response['tasks'], 200)
            : $this->getResponse('error', $response['msg'], $response['code']);
    }

    /**
     * Store a newly created task in storage.
     * @param \App\Http\Requests\Task\StoreFormRequest $storeFormRequest
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function store(StoreFormRequest $storeFormRequest)
    {
        $validated = $storeFormRequest->validated();
        $response = $this->taskService->createTask($validated);
        return $response['status']
            ? $this->getResponse('msg', 'Created task is successfully', 201)
            : $this->getResponse('error', $response['msg'], $response['code']);
    }

    /**
     * Display the specified task.
     * @param mixed $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function show($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return $this->getResponse('error', 'Not Found This Task!', 404);
        }
        $response = $this->taskService->show($task);
        return $response['status']
            ? $this->getResponse('task', $response['task'], 200)
            : $this->getResponse('error', 'This error in server', 500);
    }

    /**
     * Update the specified task in storage.
     * @param \App\Http\Requests\Task\UpdateFormRequest $updateFormRequest
     * @param mixed $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update(UpdateFormRequest $updateFormRequest, $id)
    {
        $task = Task::find($id);
        if (!$task) {
            return $this->getResponse('error', 'Not Found This Task', 404);
        }
        $validated = $updateFormRequest->validated();
        $response = $this->taskService->update($validated, $task);
        return $response['status']
            ? $this->getResponse('msg', 'Updated Task Successfully', 200)
            : $this->getResponse('error', $response['msg'], $response['code']);
    }

    /**
     * Remove the specified task from storage.
     * @param mixed $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return $this->getResponse('error', 'Not Found This Task', 404);
        }
        $response = $this->taskService->delete($task);
        return $response['status']
            ? $this->getResponse('msg', 'Task deleted successfully', 200)
            : $this->getResponse('error', $response['msg'], $response['code']);
    }

    /**
     * Retrive the specified task after deleted.
     * @param mixed $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function restore($id)
    {
        $task = Task::onlyTrashed()->find($id);
        if (!$task) {
            return $this->getResponse('error', 'Task not found or not soft-deleted', 404);
        }
        $response = $this->taskService->restore($task);
        return $response['status']
            ? $this->getResponse('msg', 'Task restored successfully', 200)
            : $this->getResponse('error', 'Failed to restore task', 500);
    }

    /**
     * Assign task to specified user
     * @param \App\Http\Requests\Task\AssignFormRequest $assignFormRequest
     * @param mixed $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function assign(AssignFormRequest $assignFormRequest, $id)
    {
        $task = Task::find($id);
        if (!$task) {
            return $this->getResponse('error', 'Not Found This Task', 404);
        }
        $validated = $assignFormRequest->validated();
        $response = $this->taskService->assign($validated, $task);
        return $response['status']
            ? $this->getResponse('msg', 'Assigned task successfully', 200)
            : $this->getResponse('error', $response['msg'], $response['code']);
    }

    /**
     * Deliveried task to admin
     * @param mixed $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function taskDelivery($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return $this->getResponse('error', 'Not Found This Task', 404);
        }
        $response = $this->taskService->delivery($task);
        return $response['status']
            ? $this->getResponse('msg', 'Task Deliveried Successfully', 200)
            : $this->getResponse('error', $response['msg'], $response['code']);
    }
}
