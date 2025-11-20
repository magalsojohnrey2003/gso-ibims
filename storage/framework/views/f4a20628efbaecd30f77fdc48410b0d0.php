
<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="py-8 px-4">
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="bg-white rounded-3xl shadow-xl border border-purple-100 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-10">
                    <p class="text-sm uppercase tracking-widest font-semibold text-white/80">Policy</p>
                    <h1 class="text-3xl font-black mt-2">Terms and Conditions for GSO-IBIMS</h1>
                    <p class="text-sm mt-3 text-white/80">Last Updated: November 15, 2025</p>
                </div>

                <div class="px-8 py-10 space-y-8 text-gray-700 leading-relaxed">
                    <p>Welcome to the General Services Office - Inventory and Borrowing Management System (GSO-IBIMS). These Terms and Conditions (&ldquo;Terms&rdquo;) govern your access to and use of our system. Please read them carefully. By creating an account or using the GSO-IBIMS, you agree to be bound by these Terms and our Privacy Policy.</p>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">1. Acceptance of Terms</h2>
                        <p>This agreement is between you (the &ldquo;User&rdquo;) and the General Services Office (the &ldquo;GSO&rdquo;). By accessing or using this system, you confirm that you are authorized to do so and that you agree to all terms and conditions outlined below. If you do not agree, you must not use this system.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">2. System Services</h2>
                        <p>The GSO-IBIMS is an online platform designed to manage and facilitate:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><span class="font-semibold">Item Borrowing:</span> Browsing available inventory, submitting official requests to borrow items, tracking the status of requests, and managing the return of borrowed items.</li>
                            <li><span class="font-semibold">Manpower Requests:</span> Submitting official requests for GSO manpower for specific tasks, locations, and durations.</li>
                            <li><span class="font-semibold">Accountability:</span> Tracking all borrowed items and requested services to ensure proper use and return.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">3. User Accounts</h2>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><span class="font-semibold">Registration:</span> To use the GSO-IBIMS, you must register for an account. You agree to provide information that is accurate, complete, and current. Based on the system's design, your account may be created by an administrator or through self-registration.</li>
                            <li><span class="font-semibold">Account Security:</span> You are responsible for safeguarding your password and for all activities that occur under your account. You must notify the GSO immediately upon learning of any breach of security or unauthorized use of your account.</li>
                            <li><span class="font-semibold">Account Termination:</span> The GSO reserves the right to suspend or terminate your account at any time, without prior notice, if you are found to be in breach of these Terms, misusing the system, or for any other reason deemed necessary for system integrity.</li>
                        </ul>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-xl font-semibold text-gray-900">4. Terms of Borrowing (Items)</h2>
                        <p><span class="font-semibold">Submitting a Request:</span> Submitting a borrow request (&ldquo;Borrow Request&rdquo;) is a formal application and <strong>does not guarantee approval</strong>. All requests are subject to review by GSO administrators.</p>
                        <p><span class="font-semibold">Request Details:</span> You are responsible for providing accurate and complete information for your request, including the purpose, office, location of use (&ldquo;location&rdquo;), and the exact borrow and return dates (&ldquo;borrow_date&rdquo;, &ldquo;return_date&rdquo;).</p>
                        <p><span class="font-semibold">Approval and Rejection:</span> The GSO reserves the full right to approve, modify (e.g., adjust quantities), or reject any borrow request. Rejections may be based on item availability, purpose, user borrowing history, or failure to provide required documentation (like a &ldquo;letter_path&rdquo;).</p>
                        <p><span class="font-semibold">Delivery and Receipt:</span> Upon dispatch (&ldquo;dispatched_at&rdquo;), you will be notified. <strong>It is your responsibility to confirm receipt of the items.</strong> The system allows you to either confirm delivery (&ldquo;confirmDelivery&rdquo;) or report that items were not received (&ldquo;reportNotReceived&rdquo;). Failure to do so may affect your accountability.</p>
                        <p><span class="font-semibold">Responsibility and Care:</span> From the moment you confirm receipt (or from the time of delivery/pickup) until the item is officially returned and processed by the GSO, <strong>you are fully responsible for the borrowed item(s)</strong>. You must use the items only for the official purpose stated in your request.</p>
                        <p><span class="font-semibold">Damage and Loss:</span> You are required to report any damage to a borrowed item immediately using the system's &ldquo;Report Damage&rdquo; feature (&ldquo;ItemDamageReportController&rdquo;). You may be held liable for the cost of repair or replacement for any items damaged, lost, or stolen while in your custody.</p>
                        <p><span class="font-semibold">Return:</span> All items must be returned on or before the agreed-upon &ldquo;return_date&rdquo;. Failure to return items on time may result in penalties, suspension of borrowing privileges, or other administrative actions.</p>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-xl font-semibold text-gray-900">5. Terms of Service (Manpower Requests)</h2>
                        <p><span class="font-semibold">Submitting a Request:</span> Submitting a manpower request (&ldquo;ManpowerRequest&rdquo;) is a formal application and <strong>does not guarantee approval</strong>.</p>
                        <p><span class="font-semibold">Request Details:</span> You must provide accurate details regarding the quantity of personnel, the required role (&ldquo;manpower_role_id&rdquo;), the specific purpose, and the start and end dates/times (&ldquo;start_at&rdquo;, &ldquo;end_at&rdquo;).</p>
                        <p><span class="font-semibold">Approval and Rejection:</span> The GSO reserves the right to approve, modify, or reject any manpower request based on personnel availability, the nature of the task, or other operational considerations.</p>
                        <p><span class="font-semibold">Supervision:</span> You or your designated office are responsible for providing on-site supervision (if applicable) and ensuring a safe work environment for the assigned GSO personnel.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">6. Acceptable Use</h2>
                        <p>You agree not to use the GSO-IBIMS to:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Submit false, inaccurate, or misleading information (e.g., in your user profile, borrow requests, or manpower requests).</li>
                            <li>Engage in any activity that is fraudulent, illegal, or for personal (non-official) gain.</li>
                            <li>Attempt to interfere with, compromise the system integrity or security, or decipher any transmissions to or from the servers running the system.</li>
                            <li>Bypass any security measures, including user roles (&ldquo;role:admin&rdquo;, &ldquo;role:user&rdquo;) or signed routes, to access parts of the system you are not authorized to access.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">7. Data Privacy</h2>
                        <p>By using this system, you consent to the collection and use of your personal information. The GSO-IBIMS collects data necessary for its function, including, but not limited to:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>Your full name (&ldquo;first_name&rdquo;, &ldquo;middle_name&rdquo;, &ldquo;last_name&rdquo;)</li>
                            <li>Your contact information (&ldquo;phone&rdquo;, &ldquo;email&rdquo;)</li>
                            <li>Your official address (&ldquo;address&rdquo;)</li>
                            <li>Records of your past and present borrow and manpower requests.</li>
                        </ul>
                        <p>This information is used solely for the purpose of managing GSO services, verifying your identity, communicating with you about your requests, and ensuring accountability. We will not share your personal data with third parties except as required by law.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">8. Disclaimers and Limitation of Liability</h2>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>The GSO-IBIMS is provided on an &ldquo;AS IS&rdquo; and &ldquo;AS AVAILABLE&rdquo; basis. The GSO does not guarantee that the system will be error-free or uninterrupted.</li>
                            <li>The GSO is not liable for any delays, failures, or damages resulting from the unavailability of items or manpower.</li>
                            <li>The GSO's liability is limited to the services provided through this system. In no event shall the GSO be liable for any indirect, incidental, or consequential damages.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">9. Modifications to Terms</h2>
                        <p>The GSO reserves the right to modify these Terms at any time. We will provide notice of any significant changes. Your continued use of the GSO-IBIMS after such changes constitutes your acceptance of the new Terms.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">10. Governing Law</h2>
                        <p>These Terms shall be governed and construed in accordance with the laws of the Republic of the Philippines, without regard to its conflict of law provisions.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-3">11. Contact Us</h2>
                        <p>If you have any questions about these Terms, please contact the General Services Office.</p>
                    </section>

                    <p class="text-sm text-gray-500 border-t pt-4">Disclaimer: This is a comprehensive draft based on the technical files of your system. It is strongly recommended that you have this document reviewed by a legal professional to ensure it fully complies with all local and national laws and your organization's specific policies.</p>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/user/terms/index.blade.php ENDPATH**/ ?>