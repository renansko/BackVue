<?php

namespace App\Domain\Services;

use App\Http\Responses\ApiModelErrorResponse;
use App\Http\Responses\ApiModelResponse;
use App\Models\Contact;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
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
            
            // Process phone numbers (now supports arrays)
            $phoneNumbers = [];
            if (isset($userData['phones'])) {
                $phoneNumbers = $this->validatePhoneNumbers($userData['phones']);
                // Check for conflicts with existing phones
                $conflictingPhones = $this->alreadyExistsPhones($phoneNumbers);
                if ($conflictingPhones) {
                    Log::info(User::class . ' : Phones already exist for other users : function-createUser', 
                        ['conflicting_phones' => $conflictingPhones]);
                    
                    return new ApiModelErrorResponse(
                        'One or more phone numbers already exist for other users',
                        new ConflictHttpException(),
                        ['conflicting_phones' => $conflictingPhones],
                        409
                    );
                }
                
                // Remove from userData as we'll handle phones separately
                unset($userData['phones']);
            }
            
            Log::info(User::class . ' Creating user : function-createUser', ['user data' => $userData]);

            $userExistente = User::where('email', $userData['email'])->withTrashed()->first();
            if ($userExistente && $userExistente->trashed()) {
                // Restore soft-deleted user
                $userExistente->restore();
                
                // Update the restored user's phone numbers if provided
                if (!empty($phoneNumbers)) {
                    $userExistente->contacts()->delete();
                    
                    foreach ($phoneNumbers as $phone) {
                        $userExistente->contacts()->create(['phone' => $phone]);
                    }
                }
    
                DB::commit();
                $response = new ApiModelResponse('User restored successfully!', $userExistente->load('contacts'), 200);
                return $response;
            } elseif ($userExistente) {
                throw new ConflictHttpException('The email is already in use.');
            }

            $user->fill([
                'name'          => $userData['name'],
                'email'         => $userData['email'],
                'password'      => Hash::make($userData['password']),
                'company_id'    => $userData['company_id'] ?? null
            ]);
      
            if ($user->save()) {
                // Add all phone numbers
                $phonesAdded = true;
                if (!empty($phoneNumbers)) {
                    foreach ($phoneNumbers as $phone) {
                        $contact = $user->contacts()->create(['phone' => $phone]);
                        if (!$contact) {
                            $phonesAdded = false;
                            break;
                        }
                    }
                }

                if (!empty($phoneNumbers) && !$phonesAdded) {
                    DB::rollBack();
                    $response = new ApiModelErrorResponse(
                        'Error adding phone numbers', 
                        new Exception(), 
                        [], 
                        500
                    );
                    return $response;
                }
                
                Log::info(User::class . ' Created user : function-createUser', ['user created' => $user]);
                DB::commit();
                $response = new ApiModelResponse('User created successfully', $user->load('contacts'), 201);
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

    public function update(User $user, array $updatedArray)
    {
        try {
            DB::beginTransaction();
            Log::info(User::class . ' : Updating user : function-update', ['user_id' => $user->id]);

            // Extract phone numbers from the update data
            $phoneNumbers = [];
            if (isset($updatedArray['phones'])) {
                $phoneNumbers = $this->validatePhoneNumbers($updatedArray['phones']);
                
                unset($updatedArray['phones']);
            }

            // Check if any phone numbers are already in use by other users
            $conflictingPhones = $this->alreadyExistsPhones($phoneNumbers, $user->id);
            if ($conflictingPhones) {
                Log::info(User::class . ' : Phones already exist for other users : function-update', 
                    ['conflicting_phones' => $conflictingPhones]);
                
                return new ApiModelErrorResponse(
                    'One or more phone numbers already exist for other users',
                    new Exception(),
                    ['conflicting_phones' => $conflictingPhones],
                    409
                );
            }

            $user->fill($updatedArray);

            $userChanged = $user->isDirty();
            if ($userChanged) {
                $user->save();
            }

            $phonesChanged = false;
            if (!empty($phoneNumbers)) {
                $currentPhones = $user->contacts()->pluck('phone')->toArray();
                
                // Only process if there are differences
                $phonesToAdd = array_diff($phoneNumbers, $currentPhones);
                $phonesToRemove = array_diff($currentPhones, $phoneNumbers);
                
                if (!empty($phonesToAdd) || !empty($phonesToRemove)) {
                    $phonesChanged = true;
                    
                    // Remove phones not in the new list
                    if (!empty($phonesToRemove)) {
                        $user->contacts()->whereIn('phone', $phonesToRemove)->delete();
                    }
                    
                    // Add new phones
                    foreach ($phonesToAdd as $phone) {
                        $user->contacts()->create(['phone' => $phone]);
                    }
                }
            }

            DB::commit();
            
            if ($userChanged || $phonesChanged) {
                Log::info(User::class . ' : Successfully updated : function-update', 
                    ['user_id' => $user->id, 'user_changed' => $userChanged, 'phones_changed' => $phonesChanged]);
                
                // Reload user with contacts to return the updated data
                $user = $user->fresh(['contacts']);
                
                return new ApiModelResponse(
                    'User updated successfully!',
                    $user,
                    200
                );
            } else {
                Log::info(User::class . ' : No changes made : function-update', ['user_id' => $user->id]);
                return new ApiModelResponse(
                    'No changes made',
                    $user->load('contacts'),
                    200
                );
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error(User::class . ' : Error updating user : function-update', 
                ['user_id' => $user->id, 'error' => $e->getMessage()]);
            
            return new ApiModelErrorResponse(
                'Unable to update user',
                $e,
                [],
                500
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

    public function searchUserNews(User $user, array $filters = []): array|ApiModelErrorResponse
    {
        try {
            Log::info(User::class . ' : search news for user : function-searchUserNews', ['user_id' => $user->id]);
            
            $perPage        = $filters['per_page'] ?? 15;
            $page           = $filters['page'] ?? 1;
            
            $newsQuery = $user->news();
            
            // Execute pagination on the relationship
            $paginatedNews = $newsQuery->paginate($perPage, ['*'], 'page', $page);
            
            Log::info(User::class . ' : found news for user : function-searchUserNews', [
                'user_id' => $user->id,
                'news_count' => $paginatedNews->total()
            ]);
            
            return [
                'user' => $user->toArray(),
                'news' => $paginatedNews
            ];

        } catch (Exception $e) {
            Log::error(User::class . ' : Error retrieving user news : function-searchUserNews', 
                ['error' => $e->getMessage()]
            );
            return new ApiModelErrorResponse(
                'Unable to retrieve news for this user',
                $e,
                [],
                400
            );
        }
    }

    private function validatePhoneNumbers($phones): array
    {
        $sanitizedPhones = [];
        
        if (is_array($phones)) {
            foreach ($phones as $phone) {
                if (!empty($phone)) {
                    $sanitizedPhones[] = preg_replace('/[^0-9]/', '', $phone);
                }
            }
        } elseif (!empty($phones)) {
            $sanitizedPhones[] = preg_replace('/[^0-9]/', '', $phones);
        }
        
        return $sanitizedPhones;
    }

    private function alreadyExistsPhones(array $phoneNumbers, ?string $excludeUserId = null): ?array
    {
        if (empty($phoneNumbers)) {
            return null;
        }
        
        $query = Contact::whereIn('phone', $phoneNumbers);
        
        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }
        
        $existingPhones = $query->get();
        
        if ($existingPhones->isNotEmpty()) {
            return $existingPhones->pluck('phone')->toArray();
        }
        
        return null;
    }
    
}