<?php

namespace Korona\Http\Controllers\Backend;

use Illuminate\Http\Request;

use Carbon\Carbon;
use Image;
use Korona\Country;
use Korona\Http\Controllers\Controller;
use Korona\Http\Requests;
use Korona\Member;
use Korona\Repositories\MemberRepository;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:backend.manage.members');
    }

    public function index(MemberRepository $memberRepo)
    {
        $members = $memberRepo->getAll();

        return view('backend.members.index', compact('members'));
    }

    public function trash(MemberRepository $memberRepo)
    {
        $members = $memberRepo->getTrashed();

        return view('backend.members.trash', compact('members'));
    }

    public function create()
    {
        return view('backend.members.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'slug' => 'required|max:255|alpha_dash',
            'nickname' => 'string|max:255',
            'firstname' => 'required|max:255',
            'lastname' => 'required|max:255',
            'sex' => 'in:MALE,FEMALE'
        ]);

        $member = new Member;
        $member->slug = $request->slug;
        $member->nickname = $request->nickname;
        $member->firstname = $request->firstname;
        $member->lastname = $request->lastname;
        $member->sex = $request->sex;
        $member->birthday = Carbon::createFromDate(2000, 01, 01);
        $member->active = $request->has('active');

        $member->save();

        return redirect()->route('backend.member.edit', $member)
               ->with('success', trans('backend.saved'));
    }

    public function show(Member $member)
    {
        //
    }

    public function edit(MemberRepository $memberRepo, Member $member)
    {
        $members = $memberRepo->getSelectData();

        $countries = Country::all()->map(function ($item) {
            $item->displayName = $item->name . ' (+' . $item->phoneprefix . ')';
            return $item;
        })->pluck('displayName', 'id');

        return view('backend.members.edit', compact('member', 'members', 'countries'));
    }

    public function update(Request $request, Member $member)
    {
        $this->validate($request, [
            'parent_id' => 'exists:members,id|not_in:'.$member->id,
            'slug' => 'required|max:255|alpha_dash|unique:members,slug,'.$member->id,
            'nickname' => 'string|max:255',
            'firstname' => 'required|max:255',
            'lastname' => 'required|max:255',
            'birthname' => 'max:255',
            'sex' => 'in:MALE,FEMALE',
            'title_prefix' => 'max:255',
            'title_suffix' => 'max:255',
            'profession' => 'max:255',
            'birthday' => 'date_format:d.m.Y',
            'status' => 'string|in:'.implode(',', settings('fraternity.member_status_enum')),
            'picture' => 'string',
            'profile_picture' => 'exists:images,id,imageable_type,Korona\Member,imageable_id,'.$member->id,
        ]);

        if ($request->birthday == $member->birthday->format('d.m.Y')) {
            // Disable revision history of the birthday if it hasn't changed.
            // Revisionable doesn't work well with Carbon instances of dates.
            $member->disableRevisionField('birthday');
        }

        $member->parent_id = $request->has('parent_id') ? $request->parent_id : null;
        $member->slug = $request->slug;
        $member->nickname = $request->nickname;
        $member->firstname = $request->firstname;
        $member->lastname = $request->lastname;
        $member->inverse_name_order = $request->has('inverse_name_order');
        $member->birthname = $request->birthname;
        $member->sex = $request->sex;
        $member->title_prefix = $request->title_prefix;
        $member->title_suffix = $request->title_suffix;
        $member->profession = $request->profession;
        $member->birthday = $request->birthday;
        $member->status = $request->status;
        $member->active = $request->has('active');

        if ($request->has('picture')) {
            $imageData = Image::make($request->picture);
            $image = new \Korona\Media\Image();
            $image->name = 'picture_'.$member->id.'_'.date('Y-m-d_H-i-s');
            $image->type = 'jpg';
            $image->public = false;
            $image->imageable_type = 'Korona\Member';
            $image->imageable_id = $member->id;
            $image->generatePath();
            $image->saveFile($imageData);
            $image->save();
            $member->profile_picture = $image->id;
        }

        if ($request->has('profile_picture')) {
            $member->profile_picture = $request->profile_picture;
        }

        if ($request->has('delete_picture')) {
            $image = \Korona\Media\Image::find($member->profile_picture);
            if ($image !== null) {
                $member->profile_picture = null;
                $member->save();
                $image->delete();
            }
        }

        $member->save();

        return redirect()->route('backend.member.edit', $member)
               ->with('success', trans('backend.saved'));
    }

    public function destroy(Member $member)
    {
        $member->delete();

        return redirect()->route('backend.member.index')
               ->with('success', trans('backend.member_deleted', ['member' => $member->getFullName()]));
    }

    public function purge($id)
    {
        $member = Member::onlyTrashed()->findOrFail($id);

        $member->forceDelete();

        return redirect()->route('backend.member.trash')
               ->with('success', trans('backend.member_purged', ['member' => $member->getFullName()]));
    }

    public function emptyTrash()
    {
        Member::onlyTrashed()->forceDelete();

        return redirect()->route('backend.member.trash')
               ->with('success', trans('backend.trash_emptied'));
    }

    public function restore($id)
    {
        $member = Member::withTrashed()->findOrFail($id);

        $member->restore();

        return redirect()->route('backend.member.trash')
               ->with('success', trans('backend.member_restored', ['member' => $member->getFullName()]));
    }
}
