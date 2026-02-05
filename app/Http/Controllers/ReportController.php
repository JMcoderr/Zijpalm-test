<?php

namespace App\Http\Controllers;

use App\FileType;
use App\Http\Requests\ReportRequest;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Assign reports based on activity or year, include activity for activity reports
//        $activityReports = Report::orderByDesc('created_at')->has('activity')->with('activity')->get();
//        $yearReports = Report::orderByDesc('created_at')->doesntHave('activity')->whereNotNull('year')->get()->sortByDesc('year');

        // Merge activity reports without existing activities
//        $activityReports = $activityReports->merge(Report::orderByDesc('created_at')->doesntHave('activity')->whereNull('year')->get());

        $reports = Report::query()->orderbyDesc('created_at')->withWhereHas('content', fn($q) => $q->where('fileType', FileType::Pdf))->get();
        $activities = $reports->whereNull('year');
        $years = $reports->whereNotNull('year');
        // Return the view, compact the variables to send with
        return view('reports.index', compact(['activities', 'years']));
    }

    /**
     * Return Report create view with the year reports years that are available.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        // Initialise the activities variable to prevent undefined errors
//        $activities = collect();

        // If no activity was given, give all activities that ended before today without reports (for select options)
//        if($activity === null && !$yearly){
//            $activities = Activity::whereBeforeToday('end')->doesntHave('report')->get()->map(
//                fn($activity) => ['id' => $activity->id, 'option' => $activity->title]
//            );
//        }
//        else if($activity){
//            $activities = collect([
//                ['id' => $activity->id, 'option' => $activity->title]
//            ]);
//        }

        // Needs the boolean from request, for some reason
//        $yearly = request()->boolean('yearly');

        // Return all reports without years
        $yearsAvailable = collect(range(now()->year, now()->subDecade()->year))->diff(Report::whereNotNull('year')->pluck('year'))->values();
        // Return the view, compact the variables to send with
        return view('reports.create', compact(['yearsAvailable']));
    }

    /**
     * Create a report from validate data.
     * Db transaction to catch possible issues when creating a report and then content
     *
     * Content created for a report so that the pdf can be associated with one.
     *
     * @param ReportRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ReportRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();
        // Create the hollow shell of a report
        DB::transaction(function () use ($validated) {
            $report = Report::query()->create([
                'year' => $validated['report-is-year'] != '-' ? $validated['report-is-year'] : null,
            ]);

            $content = $report->content()->create([
                'type'     => 'report',
                'title'    => $validated['report-title'],
                'name'     => $validated['report-title'],
                'fileType' => FileType::Pdf,
                'filePath' => $validated['report-file']->store('content/pdf', 'public'),
            ]);

            // Now manually set the foreign key on the report
            $report->update(['content_id' => $content->id]);
        });
        return redirect()->route('report.index')->with('success', $request['report-title'].' succesvol aangemaakt!');
//
//
//        $report = Report::create();
//
//        // If it's an activity report
//        if($request['report-type'] === 'Activiteit'){
//            $fileType = FileType::Image;
//            $filePath = uploadImage($request['activity-report-image'], 'images/reports/');
//            $report->activity()->associate(Activity::find($request['activity-select']));
//            $text = decodeEditorData($request['activity-report-text']);
//        }
//
//        // Else if it's a yearly report
//        else if($request['report-type'] === 'Jaar'){
//            $fileType = FileType::Pdf;
//            $filePath = $request['yearly-report-file']->store('content/pdf', 'public');
//            $report->year = $request['yearly-report-year'];
//        }
//
//        // Creates and associates the related content model
//        $report->content()->associate(
//            Content::create(
//                [
//                    'type' => lcfirst(strtok($request['report-title'], ' ')),
//                    'name' => $request['report-title'],
//                    'fileType' => $fileType,
//                    'filePath' => $filePath,
//                    'title' => $request['report-title'],
//                    'text' => $text ?? null,
//                ]
//            )
//        );
//
//        // Save changes (associated activity, created content)
//        $report->save();
//
//        return redirect()->route('report.index')->with('success', $request['report-title'].' succesvol aangemaakt!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report)
    {
        return view('reports.read', compact('report'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Report $report)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        //
        $report->delete();

        return back()->with('success', 'Verslag succesvol verwijderd!');
    }
}
