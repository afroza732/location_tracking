<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\app\AppSetting\AttendenceSetting;
use App\Models\gnr\GnrBranch;
use App\Models\hr\Attendence;
use App\Models\hr\AttUserLogInfo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
class EmployeeAttendenceController extends Controller
{
    public function index(){

    }
    public function store(Request $request){
        //return $request->all();
        $validator = Validator::make($request->all(), [
            'location'        => 'required',
            'time'            => 'required',
        ]);
        if ($validator->passes()) {
            //point checking start
            $branchArea = GnrBranch::where("companyId",auth()->user()->company_id_fk)->whereNotNull("branch_area")->select("id","branch_area")->get();
            $point      = explode (",", $request->location);
            $location   = existLocation($branchArea,$request->location,$point);
            $is_inside  = $location[0];
            $branch_id  = $location[1];
            
            //attendendence setting 
            $is_exist_attendence_setting = AttendenceSetting::first();
            $accepted_distance = ($is_exist_attendence_setting && $is_exist_attendence_setting->accepted_distance != 0) ?  $is_exist_attendence_setting->accepted_distance : null;
            if(!$is_inside &&  !$accepted_distance){
                return response()->json([
                    'status'       => false,
                    'message'      => "Your location is outside branch area!",
                ],201);
            }
            //distance wise check attendence
            $branch_id = getBranch($branchArea,$point,$accepted_distance);
            if(!$branch_id){
                return response()->json([
                    'status'       => false,
                    'message'      => "Your location is outside branch area!",
                ],201); 
            }
            //return $branch_id;
            //$attendence = Attendence::where("company_id_fk",auth()->user()->company_id_fk)->where("users_id_fk",auth()->user()->id)->where("device","app")->whereDate("attendence_date","=",Carbon::now())->get();
            // if($request->type == "entry"){
            //     $message  = "Created";
            //     $existAttendence = $attendence->first();
            //     if($existAttendence){
            //         return response()->json([
            //             'status'       => false,
            //             'message'      => "Attendence already submitted!",
            //         ],201);
            //     }
            // }
            // $existsEntry = $attendence->where("entry_time","!=",null)->first();
            // if($existsEntry && !empty($existsEntry->entry_time) &&  $existsEntry->exit_time != "00:00:00"){
            //     return response()->json([
            //         'status'       => false,
            //         'message'      => "Already checkout submitted!",
            //     ],201);
            // }
            // if($request->type == "exit"){
            //     $message  = "Updated";
            //     if(!$existsEntry){
            //         return response()->json([
            //             'status'       => false,
            //             'message'      => "Check in first!",
            //         ],201);
            //     }
            // }
            DB::transaction(function () use ($request,$branch_id) {
                try {
                    //return $request->all();
                    //$attendence                     = (!$existsEntry) ? new Attendence() : $existsEntry;
                    $attendence                      = new Attendence();
                    $attendence->users_id_fk         = auth()->user()->id;
                    $attendence->status              = "Present";
                    $attendence->entry_time          = $request->time;
                    $attendence->attendence_location = $request->location;
                    $attendence->attendence_date     = Carbon::now();
                    $attendence->company_id_fk       = auth()->user()->company_id_fk;
                    $attendence->branch_id_fk        = $branch_id;
                    $attendence->created_by          = auth()->user()->id;
                    $attendence->updated_by          = auth()->user()->id;
                    $attendence->device              = "app";
                    $attendence->device_id           = $request->device_id;
                    $attendence->save(); 
                } catch (\Exception $exception) {
                    return response()->json($exception);
                }
            });
            return response()->json([
                'status'       => true,
                'message'      => "Created successfully!",
            ],200);
        }
        return response()->json(['errors' => $validator->errors()]);
    }
}

