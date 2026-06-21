<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminEnvironmentSnapshot;
use App\Support\ApplicationLogReader;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class LogController extends Controller
{
    public function __construct(
        private ApplicationLogReader $logs,
    ) {}

    public function index(Request $request): View
    {
        $files = $this->logs->files();
        $selected = $request->string('file')->toString();

        if ($selected === '' && $files->isNotEmpty()) {
            $selected = $files->first()['filename'];
        }

        $lines = (int) $request->input('lines', 500);
        $errorsOnly = $request->boolean('errors_only');
        $content = null;
        $error = null;

        if ($selected !== '') {
            try {
                $content = $this->logs->tail($selected, $lines, $errorsOnly);
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return view('admin.logs.index', [
            'files' => $files,
            'selected' => $selected,
            'content' => $content,
            'lines' => $lines,
            'errorsOnly' => $errorsOnly,
            'error' => $error,
            'logDirectory' => $this->logs->directory(),
            'environment' => AdminEnvironmentSnapshot::items(),
        ]);
    }
}