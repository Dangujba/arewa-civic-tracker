<?php
namespace App\Policies;
use App\Models\CivicCase;
use App\Models\User;
class CivicCasePolicy {
    public function viewAny(User $u):bool{return (bool)$u->is_active;}
    public function view(User $u,CivicCase $c):bool{return $u->isAdmin() ||
          ($u->is_active && $u->role==='meal' && (!$c->reviewer_id || (int)$c->reviewer_id === (int)$u->id)) ||
          ($u->is_active && (int)$c->reporter_id === (int)$u->id);}
    public function create(User $u):bool{return $u->is_active && in_array($u->role,['focal','admin'],true);}
    public function review(User $u,CivicCase $c):bool{
        return $u->isAdmin() ||
         ($u->canReview() && (!$c->reviewer_id || (int)$c->reviewer_id === (int)$u->id));
    }
    public function approve(User $u,CivicCase $c):bool{return $u->isAdmin();}
    public function addFollowup(User $u,CivicCase $c):bool{return $this->view($u,$c);}
}
