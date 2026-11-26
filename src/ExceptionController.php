<?php

namespace LaveroAdmin\Admin\Reporter;

use Illuminate\Http\JsonResponse;
use LaveroAdmin\Admin\Controllers\ModelForm;
use LaveroAdmin\Admin\Facades\Admin;
use LaveroAdmin\Admin\Grid;
use LaveroAdmin\Admin\Layout\Content;
use LaveroAdmin\Admin\Reporter\Actions\ViewReport;
use LaveroAdmin\Admin\Reporter\Tracer\Parser;

class ExceptionController
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(): Content
    {
        return Admin::content(function (Content $content) {
            $content->header(trans('lavero-admin-reporter::reporter.exceptions'));
            $content->description(trans('lavero-admin-reporter::reporter.exceptions_list'));

            $content->body($this->grid());
        });
    }

    public function grid(): Grid
    {
        return Admin::grid(ExceptionModel::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->id('ID')->sortable();

            $grid->column('type', trans('lavero-admin-reporter::reporter.type'))->display(function ($type) {
                $path = explode('\\', $type);
                $short = array_pop($path);
                $occ = (int) ($this->occurrences ?? 1);
                $badge = $occ > 1 ? " <span class=\"badge bg-danger ms-1\">&times;{$occ}</span>" : '';

                return htmlspecialchars($short) . $badge;
            });

            $grid->column('status', 'Status')->display(function ($status) {
                $status = $status ?: 'open';
                $colors = ['open' => 'danger', 'resolved' => 'success', 'ignored' => 'secondary'];
                $c = $colors[$status] ?? 'secondary';
                return "<span class=\"badge bg-{$c}\">" . strtoupper($status) . "</span>";
            });

            $grid->code();
            $grid->column('message', trans('lavero-admin-reporter::reporter.message'))->style('width:400px')->display(function ($message) {
                if (empty($message)) {
                    return '';
                }

                return "<code>$message</code>";
            });

            $grid->column('request', trans('lavero-admin-reporter::reporter.request'))->display(function () {
                $color = ExceptionModel::$methodColor[$this->method] ?? 'secondary';

                return sprintf(
                    '<span class="badge bg-%s me-2">%s</span><code>%s</code>',
                    $color,
                    $this->method,
                    $this->path
                );
            });

            $grid->input()->display(function ($input) {
                $input = json_decode($input, true);

                if (empty($input)) {
                    return '';
                }

                return '<pre>'.json_encode($input, JSON_PRETTY_PRINT).'</pre>';
            });

            $grid->column('created_at', trans('lavero-admin-reporter::reporter.time'));

            $grid->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->equal('status', 'Status')->select(['open' => 'Open', 'resolved' => 'Resolved', 'ignored' => 'Ignored']);
                $filter->like('type', trans('lavero-admin-reporter::reporter.type'));
                $filter->like('message', trans('lavero-admin-reporter::reporter.message'));
                $filter->between('created_at', trans('lavero-admin-reporter::reporter.time'))->datetime();
            });

            $grid->disableCreation();

            $grid->actions(function (Grid\Displayers\Actions\Actions $actions) {
                $actions->disableEdit();
                //$actions->pre(new ViewReport()); // if you want an extra button
            });
        });
    }

    public function show($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header(trans('lavero-admin-reporter::reporter.exceptions'));
            $content->description(trans('lavero-admin-reporter::reporter.request_details'));

            Admin::script('Prism.highlightAll();');

            $exception = ExceptionModel::findOrFail($id);
            $trace = "#0 {$exception->file}({$exception->line})\n";
            $frames = (new Parser($trace.$exception->trace))->parse();
            $cookies = json_decode($exception->cookies, true);
            $headers = json_decode($exception->headers, true);

            array_pop($frames);

            $view = view('lavero-admin-reporter::exception', compact('exception', 'frames', 'cookies', 'headers'));

            $content->body($view);
        });
    }

    public function updateStatus(\Illuminate\Http\Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $status = (string) $request->input('status', 'resolved');
        if (!in_array($status, ['open', 'resolved', 'ignored'], true)) {
            $status = 'resolved';
        }

        $model = ExceptionModel::findOrFail($id);
        $model->status = $status;
        $model->save();

        admin_toastr("Status updated to {$status}", 'success');
        return back();
    }

    public function clearResolved(): \Illuminate\Http\RedirectResponse
    {
        ExceptionModel::query()->whereIn('status', ['resolved', 'ignored'])->delete();
        admin_toastr('Resolved exceptions cleared.', 'success');
        return back();
    }

    public function destroy($id): JsonResponse
    {
        $ids = explode(',', $id);

        if (ExceptionModel::query()->whereIn('id', $ids)->delete()) {
            return response()->json([
                'status'  => true,
                'message' => trans('admin.delete_succeeded'),
            ]);
        } else {
            return response()->json([
                'status'  => false,
                'message' => trans('admin.delete_failed'),
            ]);
        }
    }
}
