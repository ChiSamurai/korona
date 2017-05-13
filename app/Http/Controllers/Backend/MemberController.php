<?php

namespace Korona\Http\Controllers\Backend;

use Illuminate\Http\Request;

use Korona\Http\Requests;
use Korona\Http\Controllers\Controller;
use Korona\Member;
use Korona\Repositories\UserRepository;
use Carbon\Carbon;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:backend.manage.members');
    }

    public function index()
    {
        $members = Member::all();

        return view('backend.members.index', compact('members'));
    }

    public function trash()
    {
        $members = Member::onlyTrashed()->get();

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
        ]);

        $member = new Member;
        $member->slug = $request->slug;
        $member->nickname = $request->nickname;
        $member->firstname = $request->firstname;
        $member->lastname = $request->lastname;
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

    public function edit(UserRepository $repository, Member $member)
    {
        // Get all users from the UserRepository, construct an array of logins
        // (usernames) keyed with the users' IDs, and prepend an empty element
        // to allow for no user selection.
        $users = $repository->getAll()->pluck('login', 'id')->prepend('', '')->all();
        // Get all members, create a field displayName from the member's
        // getFullName() method, create an array of displayNames keyed with
        // the members' IDs, and prepend an empty element to allow for no
        // member selection. Members are needed to select the member's parent.
        $members = Member::all()->map(function ($item) {
            $item->displayName = $item->getFullName();
            return $item;
        })->pluck('displayName', 'id')->prepend('', '')->all();

        return view('backend.members.edit', compact('member', 'members', 'users'));
    }

    public function update(Request $request, Member $member)
    {
        $this->validate($request, [
            'user_id' => 'exists:users,id',
            'parent_id' => 'exists:members,id|not_in:'.$member->id,
            'slug' => 'required|max:255|alpha_dash|unique:members,slug,'.$member->id,
            'nickname' => 'string|max:255',
            'firstname' => 'required|max:255',
            'lastname' => 'required|max:255',
            'birthname' => 'max:255',
            'title' => 'max:255',
            'profession' => 'max:255',
            'birthday' => 'date_format:d.m.Y'
        ]);

        $member->user_id = $request->has('user_id') ? $request->user_id : null;
        $member->parent_id = $request->has('parent_id') ? $request->parent_id : null;
        $member->slug = $request->slug;
        $member->nickname = $request->nickname;
        $member->firstname = $request->firstname;
        $member->lastname = $request->lastname;
        $member->birthname = $request->birthname;
        $member->title = $request->title;
        $member->profession = $request->profession;
        $member->birthday = $request->birthday;
        $member->active = $request->has('active');

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
        $member = Member::withTrashed()->findOrFail($id);

        $member->forceDelete();

        return redirect()->route('backend.member.trash')
               ->with('success', trans('backend.member_purged', ['member' => $member->getFullName()]));
    }

    public function emptyTrash()
    {
        $member = Member::onlyTrashed()->forceDelete();

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