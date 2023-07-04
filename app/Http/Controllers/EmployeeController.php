<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class EmployeeController extends Controller
{public function index()
{
    $pageTitle = 'Employee List';
    confirmDelete();
    return view('employee.index', compact('pageTitle'));

}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pageTitle = 'Create Employee';

        // RAW SQL Query
        // $positions = DB::select('select * from positions');

        // QUERY BUILDER
        // $positions = DB::table('positions')->get();

        // Eloquent
        $positions = Position::all();

        return view('employee.create', compact('pageTitle', 'positions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Get File
        $file = $request->file('cv');
        $originalFilename = $file->getClientOriginalName();
        $encryptedFilename = $file->hashName();

        // Store File
        $file->store('public/files');

        // ELOQUENT
        $employee = New Employee;
        $employee->firstname = $request->firstName;
        $employee->lastname = $request->lastName;
        $employee->email = $request->email;
        $employee->age = $request->age;
        $employee->position_id = $request->position;
        $employee->original_filename = $originalFilename;
        $employee->encrypted_filename = $encryptedFilename;
        $employee->save();

        Alert::success('Added Successfully', 'Employee Data Added Successfully.');

        return redirect()->route('employees.index');

    }






    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pageTitle = 'Employee Detail';

        // RAW SQL QUERY
        // $employee = collect(DB::select('
        //     select *, employees.id as employee_id, positions.name as position_name
        //     from employees
        //     left join positions on employees.position_id = positions.id
        //     where employees.id = ?', [$id]))->first();

        // QUERY BUILDER
        // $employee = DB::table('employees')
        //     ->select('employees.*', 'employees.id as employee_id', 'positions.name as position_name')
        //     ->leftjoin('positions', 'employees.position_id', '=', 'positions.id')
        //     ->where('employees.id', $id)
        //     ->first();

        // Eloquent
        $employee = Employee::find($id);

        return view('employee.show', compact('pageTitle', 'employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageTitle = 'Edit Employee';

        // QUERY BUILDER
        // $employee = DB::table('employees')
        //     ->select('employees.*', 'employees.id as employee_id', 'positions.name as position_name')
        //     ->leftjoin('positions', 'employees.position_id', '=', 'positions.id')
        //     ->where('employees.id', $id)
        //     ->first();

        // $positions = DB::table('positions')->get();

        // Eloquent
        $employee = Employee::find($id);
        $positions = Position::all();

        return view('employee.edit', compact('pageTitle', 'employee', 'positions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }
        Alert::success('Changed Successfully', 'Employee Data Changed Successfully.');

        return redirect()->route('employees.index');


        // QUERY BUILDER
        // DB::table('employees')
        //     ->where('id', $id)->update([
        //     'firstname' => $request->firstName,
        //     'lastname' => $request->lastName,
        //     'email' => $request->email,
        //     'age' => $request->age,
        //     'position_id' => $request->position,
        // ]);

        // ELOQUENT

    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
        {
            // QUERY BUILDER
            // DB::table('employees')
            // ->where('id', $id)
            // ->delete();
            $employee = Employee::find($id);
            $encryptedFilename = 'public/files/'.$employee->encrypted_filename;
            Storage::delete($encryptedFilename);

            // ELOQUENT
            Employee::find($id)->delete();

            return redirect()->route('employees.index');

            // Eloquent


            Employee::find($id)->delete();

            Alert::success('Deleted Successfully', 'Employee Data Deleted Successfully.');

            return redirect()->route('employees.index');

    }
    public function downloadFile($employeeId)
    {
        $employee = Employee::find($employeeId);
        $encryptedFilename = 'public/files/'.$employee->encrypted_filename;
        $downloadFilename = Str::lower($employee->firstname.'_'.$employee->lastname.'_cv.pdf');

        if(Storage::exists($encryptedFilename)) {
            return Storage::download($encryptedFilename, $downloadFilename);
        }
    }

    public function getData(Request $request)
{
    $employees = Employee::with('position');

    if ($request->ajax()) {
        return datatables()->of($employees)
            ->addIndexColumn()
            ->addColumn('actions', function($employee) {
                return view('employee.actions', compact('employee'));
            })
            ->toJson();
    }
}

}

