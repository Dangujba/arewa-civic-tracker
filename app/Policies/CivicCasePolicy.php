<?php
namespace App\Policies;
use App\Models\CivicCase;
use App\Models\User;
class CivicCasePolicy {
    public function viewAny(User $u):bool{return (bool)$u->is_active;}
    public function view(User $u,CivicCase $c):bool{return $u->canReview() || ($u->is_active && $c->reporter_id===$u->id);}
    public function create(User $u):bool{return $u->is_active && in_array($u->role,['focal','admin'],true);}
    public function review(User $u,CivicCase $c):bool{return $u->canReview();}
    public function approve(User $u,CivicCase $c):bool{return $u->isAdmin();}
    public function addFollowup(User $u,CivicCase $c):bool{return $this->view($u,$c);}
}
