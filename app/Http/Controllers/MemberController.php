<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::query()
            ->when($request->route, fn($q) => $q->where('route', $request->route))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('firstname', 'like', "%{$request->search}%")
                       ->orWhere('lastname', 'like', "%{$request->search}%")
                       ->orWhere('plate_number', 'like', "%{$request->search}%")
                       ->orWhere('member_no', 'like', "%{$request->search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return view('members.index', compact('members'));
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_no' => 'required|string|unique:members,member_no',
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'middlename' => 'nullable|string|max:100',
            'plate_number' => 'required|string|unique:members,plate_number',
            'operator_name' => 'required|string|max:150',
            'route' => 'required|in:Carmen,Cogon',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'date_joined' => 'required|date',
            'status' => ['required', 'in:active,inactive'],
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('members', 'public');
        }

        Member::create($validated);

        return redirect()->route('members.index')
            ->with('success', 'New Member Added.');
    }

    public function show(Member $member)
    {
        $member->load(['dailyDues', 'loans.payments', 'benefits', 'fuelConsumptions']);
        return view('members.show', compact('member'));
    }

    public function edit(Member $member)
    {
        return view('members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'member_no' => "required|string|unique:members,member_no,{$member->id}",
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'middlename' => 'nullable|string|max:100',
            'plate_number' => "required|string|unique:members,plate_number,{$member->id}",
            'operator_name' => 'required|string|max:150',
            'route' => 'required|in:Carmen,Cogon',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:2048',
            'remove_photo' => 'nullable|boolean',
        ]);

        if ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('members', 'public');
        } elseif ($request->boolean('remove_photo') && $member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
            $validated['photo_path'] = null;
        }

        unset($validated['photo'], $validated['remove_photo']);

        $member->update($validated);

        return redirect()->route('members.index')
            ->with('success', 'Member Information Updated.');
    }

    public function destroy(Member $member)
    {
        $member->delete(); // soft delete -- photo file is kept in case the member is restored
        return redirect()->route('members.index')
            ->with('success', 'Member Successfully Deleted.');
    }
}
