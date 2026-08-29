<?php
    /** @var \App\Models\Chef $chef */
    $inputClass = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-500 focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-600';
    $labelClass = 'block text-xs font-medium uppercase tracking-[0.12em] text-slate-600 dark:text-slate-400';
    $helpClass = 'mt-1 text-xs normal-case leading-5 tracking-normal text-slate-500 dark:text-slate-500';
    $errorClass = 'mt-1 text-xs normal-case tracking-normal text-red-700 dark:text-red-400';
    $panelClass = 'rounded-xl border border-slate-200/80 bg-white p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-950';
    $galleryPaths = collect($chef->gallery_image_paths ?? [])->filter()->values();
    $approvalConfirmed = (bool) old(
        'content_and_images_approved',
        $chef->image_rights_confirmed && $chef->profile_use_approved
    );
?>

<?php if($errors->any()): ?>
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300" role="alert">
        <p class="font-medium">Please correct the highlighted information.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<div class="space-y-5" data-chef-simple-form>
    <section class="<?php echo e($panelClass); ?>" aria-labelledby="chef-profile-heading">
        <div class="mb-6">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">1. Chef profile</p>
            <h2 id="chef-profile-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">A concise introduction and the Chef’s story</h2>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">Education, awards, career history and cooking philosophy can be written naturally inside the story rather than entered as separate fields.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="<?php echo e($labelClass); ?>">
                Chef’s public name <span class="text-red-600">*</span>
                <input type="text" name="display_name" value="<?php echo e(old('display_name', $chef->display_name)); ?>" required maxlength="150" class="<?php echo e($inputClass); ?>">
                <?php $__errorArgs = ['display_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </label>

            <label class="<?php echo e($labelClass); ?>">
                Professional title <span class="text-red-600">*</span>
                <input type="text" name="professional_title" value="<?php echo e(old('professional_title', $chef->professional_title)); ?>" required maxlength="180" placeholder="Executive Chef" class="<?php echo e($inputClass); ?>">
                <?php $__errorArgs = ['professional_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </label>

            <label class="<?php echo e($labelClass); ?>">
                Restaurant, hotel or culinary brand
                <input type="text" name="organisation_name" value="<?php echo e(old('organisation_name', $chef->organisation_name)); ?>" maxlength="200" class="<?php echo e($inputClass); ?>">
                <?php $__errorArgs = ['organisation_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </label>

            <label class="<?php echo e($labelClass); ?>">
                City
                <input type="text" name="city" value="<?php echo e(old('city', $chef->city)); ?>" maxlength="120" class="<?php echo e($inputClass); ?>">
                <?php $__errorArgs = ['city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </label>
        </div>

        <label class="<?php echo e($labelClass); ?> mt-5">
            Short Chef brief
            <textarea name="short_intro" rows="3" maxlength="700" class="<?php echo e($inputClass); ?>" placeholder="A concise 30–60 word introduction for the homepage and Chef cards."><?php echo e(old('short_intro', $chef->short_intro)); ?></textarea>
            <p class="<?php echo e($helpClass); ?>">Required before publishing.</p>
            <?php $__errorArgs = ['short_intro'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </label>

        <label class="<?php echo e($labelClass); ?> mt-5">
            The Chef’s story
            <textarea name="biography" rows="9" maxlength="12000" class="<?php echo e($inputClass); ?>" placeholder="Write one well-edited profile covering the Chef’s background, influences, experience and approach to food."><?php echo e(old('biography', $chef->biography)); ?></textarea>
            <p class="<?php echo e($helpClass); ?>">A focused editorial story of approximately 150–350 words is ideal. Required before publishing.</p>
            <?php $__errorArgs = ['biography'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </label>
    </section>

    <section class="<?php echo e($panelClass); ?>" aria-labelledby="signature-dish-heading">
        <div class="mb-6">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">2. Signature dish</p>
            <h2 id="signature-dish-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">One dish that represents the Chef</h2>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">This is an editorial showcase, not a recipe. No ingredients, method or existing Recipe record is required.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-5">
                <label class="<?php echo e($labelClass); ?>">
                    Signature dish name
                    <input type="text" name="signature_dish_name" value="<?php echo e(old('signature_dish_name', $chef->signature_dish_name)); ?>" maxlength="200" placeholder="Charred Pork Neck with Smoked Chilli Jus" class="<?php echo e($inputClass); ?>">
                    <p class="<?php echo e($helpClass); ?>">Required before publishing.</p>
                    <?php $__errorArgs = ['signature_dish_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </label>

                <label class="<?php echo e($labelClass); ?>">
                    Dish description or story
                    <textarea name="signature_dishes" rows="6" maxlength="3000" class="<?php echo e($inputClass); ?>" placeholder="Describe the inspiration, technique, flavours or why the Chef chose this dish."><?php echo e(old('signature_dishes', $chef->signature_dishes)); ?></textarea>
                    <p class="<?php echo e($helpClass); ?>">Approximately 60–150 words. Required before publishing.</p>
                    <?php $__errorArgs = ['signature_dishes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </label>
            </div>

            <div>
                <p class="<?php echo e($labelClass); ?>">Signature dish photograph</p>
                <div class="mt-1 aspect-[4/3] overflow-hidden rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                    <?php if($chef->signatureDishImageUrl()): ?>
                        <img src="<?php echo e($chef->signatureDishImageUrl()); ?>" alt="Current signature dish" class="h-full w-full object-cover">
                    <?php else: ?>
                        <div class="flex h-full items-center justify-center px-5 text-center text-xs leading-5 text-slate-500">A strong horizontal or square photograph will be displayed here.</div>
                    <?php endif; ?>
                </div>
                <input type="file" name="signature_dish_image" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 dark:text-slate-400 dark:file:bg-slate-900 dark:file:text-slate-300">
                <p class="<?php echo e($helpClass); ?>">JPG, PNG or WebP, up to 12 MB. Required before publishing.</p>
                <?php $__errorArgs = ['signature_dish_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                <?php if($chef->signature_dish_image_path): ?>
                    <label class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="remove_signature_dish_image" value="0">
                        <input type="checkbox" name="remove_signature_dish_image" value="1" <?php if(old('remove_signature_dish_image')): echo 'checked'; endif; ?> class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                        Remove current dish photograph
                    </label>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="<?php echo e($panelClass); ?>" aria-labelledby="chef-media-heading">
        <div class="mb-6">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">3. Photographs & links</p>
            <h2 id="chef-media-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">The essential public media</h2>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">A portrait is essential. A working photograph, small gallery and professional links are optional.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <p class="<?php echo e($labelClass); ?>">Chef portrait</p>
                <div class="mt-3 flex gap-4">
                    <div class="h-40 w-32 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900">
                        <?php if($chef->portraitUrl()): ?>
                            <img src="<?php echo e($chef->portraitUrl()); ?>" alt="Current Chef portrait" class="h-full w-full object-cover">
                        <?php else: ?>
                            <div class="flex h-full items-center justify-center text-xs text-slate-500">4:5 portrait</div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <input type="file" name="portrait_image" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 dark:text-slate-400 dark:file:bg-slate-900 dark:file:text-slate-300">
                        <p class="<?php echo e($helpClass); ?>">Vertical 4:5, up to 8 MB. Required before publishing.</p>
                        <?php $__errorArgs = ['portrait_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <?php if($chef->portrait_image_path): ?>
                            <label class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                <input type="hidden" name="remove_portrait_image" value="0">
                                <input type="checkbox" name="remove_portrait_image" value="1" <?php if(old('remove_portrait_image')): echo 'checked'; endif; ?> class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                                Remove current portrait
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <p class="<?php echo e($labelClass); ?>">Chef at work / kitchen photograph</p>
                <div class="mt-3 aspect-[16/9] overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900">
                    <?php if($chef->workingImageUrl()): ?>
                        <img src="<?php echo e($chef->workingImageUrl()); ?>" alt="Current working photograph" class="h-full w-full object-cover">
                    <?php else: ?>
                        <div class="flex h-full items-center justify-center text-xs text-slate-500">Optional horizontal image</div>
                    <?php endif; ?>
                </div>
                <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 dark:text-slate-400 dark:file:bg-slate-900 dark:file:text-slate-300">
                <?php $__errorArgs = ['hero_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php if($chef->hero_image_path): ?>
                    <label class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="remove_hero_image" value="0">
                        <input type="checkbox" name="remove_hero_image" value="1" <?php if(old('remove_hero_image')): echo 'checked'; endif; ?> class="rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                        Remove current working photograph
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <div>
                    <label class="<?php echo e($labelClass); ?>">
                        Optional gallery photographs
                        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-2 block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 dark:text-slate-400 dark:file:bg-slate-900 dark:file:text-slate-300">
                        <p class="<?php echo e($helpClass); ?>">Up to four public gallery images. Existing additional images are preserved but only the first four are shown.</p>
                        <?php $__errorArgs = ['gallery_images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <?php $__errorArgs = ['gallery_images.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>

                    <?php if($galleryPaths->isNotEmpty()): ?>
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <?php $__currentLoopData = $galleryPaths; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $galleryPath): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $galleryUrl = str_starts_with($galleryPath, 'http://') || str_starts_with($galleryPath, 'https://') || str_starts_with($galleryPath, '/')
                                        ? $galleryPath
                                        : \Illuminate\Support\Facades\Storage::disk('public')->url($galleryPath);
                                ?>
                                <label class="group relative block aspect-[4/3] overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900">
                                    <img src="<?php echo e($galleryUrl); ?>" alt="Chef gallery photograph" class="h-full w-full object-cover">
                                    <span class="absolute inset-x-0 bottom-0 flex items-center gap-2 bg-black/60 px-2 py-1.5 text-[11px] text-white">
                                        <input type="checkbox" name="remove_gallery_paths[]" value="<?php echo e($galleryPath); ?>" <?php if(in_array($galleryPath, old('remove_gallery_paths', []), true)): echo 'checked'; endif; ?> class="rounded border-white/60 bg-transparent text-white focus:ring-white">
                                        Remove
                                    </span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="grid content-start gap-5 sm:grid-cols-2">
                    <label class="<?php echo e($labelClass); ?> sm:col-span-2">
                        Photography credit
                        <input type="text" name="photographer_credit" value="<?php echo e(old('photographer_credit', $chef->photographer_credit)); ?>" maxlength="200" class="<?php echo e($inputClass); ?>">
                        <?php $__errorArgs = ['photographer_credit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                    <label class="<?php echo e($labelClass); ?> sm:col-span-2">
                        Website or restaurant page
                        <input type="url" name="website_url" value="<?php echo e(old('website_url', $chef->website_url)); ?>" placeholder="https://" class="<?php echo e($inputClass); ?>">
                        <?php $__errorArgs = ['website_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                    <label class="<?php echo e($labelClass); ?>">
                        Instagram
                        <input type="url" name="instagram_url" value="<?php echo e(old('instagram_url', $chef->instagram_url)); ?>" placeholder="https://" class="<?php echo e($inputClass); ?>">
                        <?php $__errorArgs = ['instagram_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                    <label class="<?php echo e($labelClass); ?>">
                        LinkedIn
                        <input type="url" name="linkedin_url" value="<?php echo e(old('linkedin_url', $chef->linkedin_url)); ?>" placeholder="https://" class="<?php echo e($inputClass); ?>">
                        <?php $__errorArgs = ['linkedin_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </label>
                </div>
            </div>
        </div>
    </section>

    <section class="<?php echo e($panelClass); ?>" aria-labelledby="chef-publishing-heading">
        <div class="mb-6">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">4. Publishing</p>
            <h2 id="chef-publishing-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white">Approval and manual homepage selection</h2>
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <label class="<?php echo e($labelClass); ?>">
                Status <span class="text-red-600">*</span>
                <select name="status" required class="<?php echo e($inputClass); ?>" data-chef-status>
                    <?php $__currentLoopData = \App\Models\Chef::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($option); ?>" <?php if(old('status', $chef->status) === $option): echo 'selected'; endif; ?>><?php echo e(ucfirst($option)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </label>

            <label class="<?php echo e($labelClass); ?>">
                Directory display order
                <input type="number" name="sort_order" min="0" max="100000" value="<?php echo e(old('sort_order', $chef->sort_order ?? 0)); ?>" class="<?php echo e($inputClass); ?>">
                <?php $__errorArgs = ['sort_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </label>

            <label class="<?php echo e($labelClass); ?>">
                Publication date
                <input type="datetime-local" name="published_at" value="<?php echo e(old('published_at', $chef->published_at?->format('Y-m-d\TH:i'))); ?>" class="<?php echo e($inputClass); ?>">
                <p class="<?php echo e($helpClass); ?>">Leave blank to publish immediately.</p>
                <?php $__errorArgs = ['published_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="<?php echo e($errorClass); ?>"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </label>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <input type="hidden" name="content_and_images_approved" value="0">
                <input type="checkbox" name="content_and_images_approved" value="1" <?php if($approvalConfirmed): echo 'checked'; endif; ?> class="mt-0.5 rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900">
                <span>
                    <span class="block text-sm font-medium text-slate-950 dark:text-white">Chef profile and images approved</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Confirm that the Chef has approved the final profile, organisation mention and supplied photographs for Bandara Kitchen publication.</span>
                    <?php $__errorArgs = ['content_and_images_approved'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="<?php echo e($errorClass); ?> block"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </span>
            </label>

            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-800" data-chef-feature-label>
                <input type="hidden" name="feature_on_homepage" value="0">
                <input type="checkbox" name="feature_on_homepage" value="1" <?php if(old('feature_on_homepage', $chef->isHomepageFeaturedSelection())): echo 'checked'; endif; ?> class="mt-0.5 rounded border-slate-300 text-slate-950 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900" data-chef-feature-checkbox>
                <span>
                    <span class="block text-sm font-medium text-slate-950 dark:text-white">Feature on homepage</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Manual launch mode. Selecting this published Chef automatically replaces the previous homepage Chef.</span>
                    <?php $__errorArgs = ['feature_on_homepage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="<?php echo e($errorClass); ?> block"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </span>
            </label>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 rounded-xl border border-slate-200/80 bg-white p-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-950">
        <p class="text-xs leading-5 text-slate-500">
            <?php if($chef->exists): ?>
                Last updated <?php echo e($chef->updated_at?->format('d M Y, H:i')); ?>. Legacy profile and recipe data remains stored but is not shown in this simplified form.
            <?php else: ?>
                Start as Draft and publish after the portrait, story, signature dish and approval are complete.
            <?php endif; ?>
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('admin.kitchen.chefs.index')); ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:border-slate-500 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500">Cancel</a>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-slate-950 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                <?php echo e($chef->exists ? 'Save Chef' : 'Create Chef'); ?>

            </button>
        </div>
    </div>
</div>

<?php if (! $__env->hasRenderedOnce('5b69a271-4260-485f-a82d-7c9fb5b50231')): $__env->markAsRenderedOnce('5b69a271-4260-485f-a82d-7c9fb5b50231'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-chef-simple-form]').forEach(function (root) {
                const statusSelect = root.querySelector('[data-chef-status]');
                const featureCheckbox = root.querySelector('[data-chef-feature-checkbox]');
                const featureLabel = root.querySelector('[data-chef-feature-label]');

                function syncFeatureAvailability() {
                    if (!statusSelect || !featureCheckbox) return;
                    const canFeature = statusSelect.value === 'published';
                    if (!canFeature) featureCheckbox.checked = false;
                    featureCheckbox.disabled = !canFeature;
                    featureLabel?.classList.toggle('opacity-60', !canFeature);
                }

                statusSelect?.addEventListener('change', syncFeatureAvailability);
                syncFeatureAvailability();
            });
        });
    </script>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/kitchen/chefs/_form.blade.php ENDPATH**/ ?>