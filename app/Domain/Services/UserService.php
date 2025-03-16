<?php

namespace App\Domain\Services;

use App\Http\Responses\ApiModelErrorResponse;
use App\Http\Responses\ApiModelResponse;
use App\Models\Contact;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UserService
{
    public function searchUsers(): ApiModelResponse|ApiModelErrorResponse
    {
        Log::info(User::class . ' Searching all users : function-searchUsers');
        $users = User::all();

        if($users->isEmpty()){
            Log::info(User::class . ' No users found : function-searchUsers');
            return new ApiModelErrorResponse('No users found', new Exception(), [], 404);
        }

        Log::info(User::class . ' Found ' . $users->count() . ' users : function-searchUsers');
        $response = new ApiModelResponse('Users retrieved successfully', $users, 200);
       
        return $response;
    }

    public function findUser(User $user): ApiModelResponse|ApiModelErrorResponse
    {
        Log::info(User::class . ' Finding user : function-findUser', ['user_id' => $user->id]);
        
        if(!$user){
            Log::warning(User::class . ' User not found : function-findUser', ['user_id' => $user->id]);
            return new ApiModelErrorResponse('User not found', new Exception(), [], 404);
        }

        Log::info(User::class . ' User found successfully : function-findUser', ['user_id' => $user->id]);
        $response = new ApiModelResponse('User found successfully', $user, 200);
       
        return $response;
    }

    public function createUser(array $userData){
        DB::beginTransaction();
        try {
            $user = new User();

            if(isset($updatedArray['phone'])){
                $updatedArray['phone'] = preg_replace('/[^0-9]/', '', $updatedArray['phone']);
            }
            
            Log::info(User::class . ' Creating user : function-createUser', ['user data' => $userData]);

            $userExistente = User::where('email', $userData['email'])->withTrashed()->first();
            if ($userExistente && $userExistente->trashed()) {
                // Caso exista um registro com soft delete, restaure-o
                $userExistente->restore();
                // $userExistente->update($userData); -> Updated If new values ( I think it's not necessary )
    
                DB::commit();
                $response = new ApiModelResponse('User restored successfully!', $userExistente, 200);
                return $response;
            } elseif ($userExistente) {
                throw new ConflictHttpException('The email is already in use.');
            }

            $user->name = $userData['name'];
            $user->email = $userData['email'];
            $user->password = Hash::make($userData['password']);

            if ($user->save()) {
                Log::info(User::class . ' Created user : function-createUser', ['user created' => $user]);
                DB::commit();
                $response = new ApiModelResponse('User created successfully', $user, 201);
            } else {
                Log::error(User::class . ' Error creating user : function-createUser', ['user data' => $userData]);
                DB::rollBack();
                $response = new ApiModelErrorResponse('Error creating user', new Exception(), [], 500);
            }

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error(User::class . ' Exception creating user : function-createUser', ['error' => $e->getMessage()]);
            return new ApiModelErrorResponse('Error creating user', $e, [], 500);
        }
    }

    public function update(User $user, array $updatedArray){
        DB::beginTransaction();

        if(isset($updatedArray['phone'])){
            $updatedArray['phone'] = preg_replace('/[^0-9]/', '', $updatedArray['phone']);
        }

        $contats = new Contact();

        $contatsAlreadyExist = $contats->find('phone', $updatedArray['phone'])->where('user_id', '!=' , $user->id)->first();

        if($contatsAlreadyExist){
            Log::info(User::class . ' : Contact already exists for another user : function-updatedUser', ['contact' => $contatsAlreadyExist]);
            return new ApiModelErrorResponse(
                'Contact already exists for another user',
                new Exception(),
                [],
                409
            );
        }

        $user->fill($updatedArray);

        if ($user->isDirty()) {
            $statusUser = $user->update();
            $user->contacts()->update($updatedArray['phone']);
            if ($statusUser) {
                DB::commit();
                Log::info(User::class . ' : Successfully updated : function-updatedUser', ['user' => $statusUser]);

                return new ApiModelResponse(
                    'User updated successfully!',
                    $user,
                    200
                );
            } else {
                $e = new Exception('Unexpected error');
                DB::rollBack();
                return new ApiModelErrorResponse(
                    'Unable to edit user',
                    $e,
                    [],
                    500
                );
            }
        } else {
            Log::info(User::class . ' : No changes made : function-updatedUser', ['user' => $user]);
            DB::commit();
            return new ApiModelResponse(
                'No changes made',
                $user,
                200
            );
        }
    }

    public function destroy(User $user): ApiModelResponse|ApiModelErrorResponse
    {
        try {
            Log::info(User::class . ' : Deleting User : function-destroy', ['user_id' => $user->id]);
            DB::beginTransaction();

            $user->delete();
            Log::info(User::class . ' : User deleted : function-destroy', ['user' => $user]);

            DB::commit();
            return new ApiModelResponse(
                'User deleted successfully!',
                $user,
                200
            );

        } catch (Exception $e) {
            DB::rollBack();
            Log::error(User::class . ' : Error deleting user : function-destroy', ['error' => $e->getMessage()]);
            return new ApiModelErrorResponse(
                'Unable to find user with this id',
                $e,
                [],
                400
            );
        }
    }
}