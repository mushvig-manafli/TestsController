<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Answers;
use App\Models\Companies;
use App\Models\CompaniesAnswers;
use App\Models\DepartmentsSciences;
use App\Models\Questions;
use App\Models\Sciences;
use App\Models\SciencesLessons;
use App\Models\Translates;
use App\Models\UsersQuestionsAnswers;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestsController extends Controller
{

   

    //get user profile
    public function getProfile($id){
        $user = Companies::where('id',$id)->first();
        $ds = DepartmentsSciences::where('departments_id',$user->departments_id)->orderBy('ordering','ASC')->get();
        $translates = Translates::whereIn('id',[103,104,105,106,107])
            ->orWhere('page','profile')
        ->get();

        $arr = [];

        foreach ($ds as $d){
            array_push($arr, $d->sciences_id);
        }

        $sciences = Sciences::whereIn('id',$arr)->orderBy('ordering','ASC')->get(); 
        return view('site.admin.profile', compact('user', 'sciences','translates'));
    }


    
    //Tests page
    public function getQuiz($id,$lessons_id){
	

        $lessons = SciencesLessons::where('sciences_id',$id)->get();
        $user_lessons = DB::table('user_lessons')
            ->where('users_id',session()->get('company')->id)
            ->where('sciences_id',$id)
            ->get();
        $final_link = false;
        $list = [];
        foreach ($user_lessons as $ul){
            if ($ul->lessons_id ==0 && $ul->passed){
                $final_link = true;
            }
            array_push($list, $ul->lessons_id);
        }

        $questions = Questions::where([
            'sciences_id' => $id,
            'lessons_id' => $lessons_id
        ])->get();

        $user_answers = UsersQuestionsAnswers::where([
            'user_id' => session()->get('company')->id,
            'sciences_id' => $id,
            'lessons_id' => $lessons_id
        ])->orderBy('id','DESC')->first();
        $start_date = 0;

        if ($user_answers){

            foreach ($questions as $index => $q){
                if ($q->id == $user_answers->questions_id){


                    if (!isset($questions[1+$index])){

                        $question = $questions[0];

                    }
                    else{

                        $question = $questions[1+$index];
                    }


                }
                else{
                    return false;
                }

            }

        }
        else{

            if (!$lessons_id){

                $check_old = DB::table('user_lessons')->where([
                    'sciences_id' => $id,
                    'lessons_id' => $lessons_id,
                    'users_id' => session()->get('company')->id
                ])->first();

                if(!$check_old->started){

                    DB::table('user_lessons')->where([
                       'sciences_id' => $id,
                       'lessons_id' => $lessons_id,
                       'users_id' => session()->get('company')->id
                    ])->update([
                        'start_date' => Carbon::now()->addMinute(30),
                        'started' => 1
                    ]);
                    $start_date = Carbon::now()->addMinute(30);
                    $started = 1;
                }
                else{
		
                    $start_date = $check_old->start_date;
			
                    $started = $start_date;


                }
            }
            $question= $questions[0];

        }

        $all_questions = Questions::where([
            'sciences_id' => $id,
            'lessons_id' => $lessons_id
        ])->get();
        $start_date = DB::table('user_lessons')->where([
            'sciences_id' => $id,
            'lessons_id' => $lessons_id,
            'users_id' => session()->get('company')->id
        ])->first();

        $question_number = 0;

        foreach($all_questions as $index => $aq){
            if ($aq->id == $question->id){
                $question_number = $index+1;
                break;
            }
        }

        $question_number = $question_number.'/'.count($all_questions);

        return view('site.admin.quiz', compact('id','question_number','start_date', 'lessons_id','question','lessons','list','final_link'));


    }

    //set test done
    public function setDone($id){
        DB::table('user_lessons')
            ->where([
                'lessons_id' => $id,
                'sciences_id' => 0,
                'users_id' => session()->get('company')->id
            ])->update([
                'passed' => 1
            ]);
        return redirect()->route('getResultsSite',['id' => $id]);
    }


    


    public function getLessons($id, $lessons_id=0){
        $start_date = DB::table('user_lessons')->where([
            'sciences_id' => $id,
            'lessons_id' => $lessons_id,
            'users_id' => session()->get('company')->id
        ])->first();

        if ($lessons_id == 'final' && $start_date->started && $start_date->start_date && $start_date->start_date <= Carbon::now()){
            return redirect()->route('getResultsSite',['id' => $id ]);
        }
        $lessons = SciencesLessons::where('sciences_id',$id)->get();
        $user_lessons = DB::table('user_lessons')
            ->where('users_id',session()->get('company')->id)
            ->where('sciences_id',$id)
            ->get();
        if (!count($user_lessons)){
            $user_lesson = DB::table('user_lessons')->insert([
                'users_id' => session()->get('company')->id,
                'sciences_id' => $id,
                'lessons_id' => $lessons[0]->id

            ]);
            $user_lesson = DB::table('user_lessons')->where([
                'users_id' => session()->get('company')->id,
                'sciences_id' => $id

            ])->get();
            $lesson = SciencesLessons::where('id',$lessons[0]->id)->first();
        }
        else{

            if (!$lessons_id){


                if (!$user_lessons[count($user_lessons)-1]->lessons_id){
                    if ($user_lessons[count($user_lessons)-1]->passed){
                        return redirect()->route('getResultsSite',['id' => $id]);
                    }
                    else{
                        return redirect()->route('getLessonsSite',['id' => $id, 'lessons_id' => 'final']);

                    }

                }
                else{

                    $user_lesson = $user_lessons[count($user_lessons)-1];
                    $lesson = SciencesLessons::where('id', $user_lesson->lessons_id)->first();
                }

            }
            else{
                $lesson = SciencesLessons::where('id', $lessons_id)->first();
            }

        }
        $final_link = false;
        $list = [];
        foreach ($user_lessons as $ul){
            if ($ul->lessons_id ==0 && $ul->passed){
                $final_link = true;
            }
            array_push($list, $ul->lessons_id);
        }


        return view('site.admin.lessons', compact('final_link','lesson','lessons','id','lessons_id','list'));

    }


    

    public function getSubjects(){
        $results = DepartmentsSciences::where([
            'companies_id' => session()->get('company')->p_id,
            'departments_id' => session()->get('company')->departments_id
        ])->orderBy('ordering','ASC')->get();
        $translates = Translates::where("page", "user")->get();
	$arr = [];
        foreach($results as $r){
            array_push($arr, $r->sciences_id);
        }

        $sciences = DB::table('user_lessons')->where([
            'users_id' => session()->get('company')->id,
            'lessons_id' => 0,
            'started' =>1
        ])->whereIn('sciences_id',$arr)->get();

        $s_arr = [];
        foreach($sciences as $s){
            array_push($s_arr, $s->sciences_id);
        }



        return view('site.admin.subjects', compact('results','s_arr','translates'));
    }




    public function nextQuestion(Request $request){

        $is_right = Answers::find($request->answer)->is_right;

        if(!($request->get('lessons_id') != 0 && !$is_right && $request->tries == 0)) {
            UsersQuestionsAnswers::create([
                'companies_id' => session()->get('company')->p_id,
                'departments_id' => session()->get('company')->departments_id,
                'user_id' => session()->get('company')->id,
                'sciences_id' => $request->get('sciences_id'),
                'lessons_id' => $request->get('lessons_id'),
                'questions_id' => $request->get('questions_id'),
                'answers_id' => $request->get('answers_id'),
                'is_right' => $is_right
            ]);
        }

        $questions = Questions::where([
            'sciences_id' => $request->get('sciences_id'),
            'lessons_id' => $request->get('lessons_id')
        ])->orderBy('id','DESC')->first();

        if($questions->id == $request->get('questions_id')){
            $lessons = SciencesLessons::where('sciences_id',$request->get('sciences_id'))->get();
            if ($request->get('lessons_id') != 0){
                foreach ($lessons as $index => $l){
                    if ($l->id == $request->get('lessons_id')){
                        if (isset($lessons[1+$index])){
                            $next_lesson = DB::table('user_lessons')->where([
                                'users_id' => session()->get('company')->id,
                                'sciences_id' =>$request->get('sciences_id'),
                                'lessons_id' => $lessons[1+$index]->id
                            ])->first();
                            if (!$next_lesson){
                                DB::table('user_lessons')->insert([
                                    'users_id' => session()->get('company')->id,
                                    'sciences_id' =>$request->get('sciences_id'),
                                    'lessons_id' => $lessons[1+$index]->id
                                ]);

                            }
                            return response()->json([
                                'is_right' => $is_right,
                                'next' => route('getLessonsSite', [
                                    'id' => $request->get('sciences_id'),
                                    'lessons_id' => $lessons[1+$index]->id
                                ])
                            ]);
                            
                        }
                        else{
                            $final = DB::table('user_lessons')->where([
                                'users_id' => session()->get('company')->id,
                                'sciences_id' =>$request->get('sciences_id'),
                                'lessons_id' => 0
                            ])->first();

                            if (!$final){
                                DB::table('user_lessons')->insert([
                                    'users_id' => session()->get('company')->id,
                                    'sciences_id' =>$request->get('sciences_id'),
                                    'lessons_id' => 0
                                ]);

                            }
                            return response()->json([
                                'is_right' => $is_right,
                                'next' => route('getLessonsSite', [
                                    'id' => $request->get('sciences_id'),
                                    'lessons_id' => '0'
                                ])
                            ]);
                        }
                    }
                }
            }
            else{
                DB::table('user_lessons')->where([
                    'users_id' => session()->get('company')->id,
                    'sciences_id' =>$request->get('sciences_id'),
                    'lessons_id' => 0
                ])->update(['passed' =>1]);

                return response()->json([
                    'is_right' => $is_right,
                    'next' => route('getResultsSite',['id' => $request->get('sciences_id')])
                ]);
            }

        }
        return response()->json([
            'is_right' => $is_right,
            'next' => route('getQuizSite', [
                'id' => $request->get('sciences_id'),
                'lessons_id' => $request->get('lessons_id')
            ])
        ]);
    }




    //get examination results
    public function getResults($id){
        $lessons = SciencesLessons::where('sciences_id',$id)->get();

        $science = Sciences::where('id',$id)->first();
        $questions = Questions::where([
            'sciences_id' => $id,
            'lessons_id' => 0
        ])->get();
        $u_answers = UsersQuestionsAnswers::where([
            'user_id' => session()->get('company')->id,
            'sciences_id' => $id,
            'lessons_id' => 0,
            'is_right' => 1
        ])->count();


        $all = Questions::where([

            'sciences_id' => $id,
            'lessons_id' =>0
        ])->count();
        $per = $u_answers.' / '.$all;
        return view('site.admin.questions-answers', compact('id','lessons','questions','per','science'));
    }
}
