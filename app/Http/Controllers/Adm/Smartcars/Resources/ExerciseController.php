<?php

namespace App\Http\Controllers\Adm\Smartcars\Resources;

use App\Models\Smartcars\Flight;
use Illuminate\Http\Request;
use App\Http\Controllers\Adm\AdmController as Controller;
use Storage;

class ExerciseController extends Controller
{
    /**
     * Define where to redirect requests.
     *
     * @return string
     */
    public function redirectTo()
    {
        return route('adm.smartcars.exercises.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('use-permission', 'smartcars/exercises');

        $exercises = Flight::query()->orderBy('created_at')->with('departure', 'arrival', 'aircraft')->paginate(50);

        return $this->viewMake('adm.smartcars.exercises')->with('exercises', $exercises);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return $this->viewMake('adm.smartcars.exercise-form');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $image = $request->file('image');
        // Storage::drive('public')->putFileAs('smartcars/exercises', $image, "id_of_exercise.{$image->extension()}");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Smartcars\Flight  $exercise
     * @return \Illuminate\Http\Response
     */
    public function show(Flight $exercise)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Smartcars\Flight  $exercise
     * @return \Illuminate\Http\Response
     */
    public function edit(Flight $exercise)
    {
        return $this->viewMake('adm.smartcars.exercise-form')->with('exercise', $exercise);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Smartcars\Flight  $exercise
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Flight $exercise)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Smartcars\Flight  $exercise
     * @return \Illuminate\Http\Response
     */
    public function destroy(Flight $exercise)
    {
        //
    }
}
