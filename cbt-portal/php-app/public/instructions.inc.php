<?php
/** @var array $attempt @var array $outline @var int $totalQ @var float $pos @var float $neg @var callable $fmt @var ?array $u */
$dur = (int) $attempt['duration_minutes'];
$maxMarks = $totalQ * $pos;
$legend = fn (string $cls, string $label, string $en, string $bn) =>
    '<div class="flex items-start gap-3"><span class="w-8 h-8 shrink-0 flex items-center justify-center text-xs font-bold ' . $cls . '">' . $label . '</span>'
    . '<p class="text-sm text-slate-700 pt-1"><span data-lang="en">' . $en . '</span><span data-lang="bn" class="hidden">' . $bn . '</span></p></div>';
?>
<div class="min-h-screen flex flex-col">
  <header class="bg-white border-b border-red-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
      <?= brand_lockup() ?>
      <div class="flex items-center gap-3 text-sm">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-xs font-semibold" role="group" aria-label="Instructions language">
          <button type="button" data-set-lang="en" class="px-2.5 py-1 rounded-md cursor-pointer bg-red-600 text-white">English</button>
          <button type="button" data-set-lang="bn" class="px-2.5 py-1 rounded-md cursor-pointer text-slate-600 hover:bg-slate-100">বাংলা</button>
        </div>
        <?php if ($u): ?><span class="hidden sm:flex items-center gap-2 font-semibold text-slate-700"><span class="w-7 h-7 rounded-full bg-red-600 text-white flex items-center justify-center text-xs"><?= h(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span><?= h($u['name']) ?></span><?php endif; ?>
      </div>
    </div>
  </header>

  <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-6">
    <div class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden">
      <div class="px-5 sm:px-7 py-5 border-b border-red-100 bg-gradient-to-r from-red-50 to-white">
        <p class="text-xs font-semibold uppercase tracking-wider text-red-600"><span data-lang="en">Read the instructions carefully</span><span data-lang="bn" class="hidden">নির্দেশাবলি মনোযোগ দিয়ে পড়ুন</span></p>
        <h1 class="mt-1 text-xl sm:text-2xl font-extrabold text-slate-900"><?= h($attempt['title']) ?></h1>
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
          <div class="rounded-xl bg-white border border-red-100 p-3"><div class="text-xl font-extrabold text-slate-900"><?= $dur ?> <span class="text-sm font-semibold">min</span></div><div class="text-[11px] text-slate-500"><span data-lang="en">Total time</span><span data-lang="bn" class="hidden">মোট সময়</span></div></div>
          <div class="rounded-xl bg-white border border-red-100 p-3"><div class="text-xl font-extrabold text-slate-900"><?= $totalQ ?></div><div class="text-[11px] text-slate-500"><span data-lang="en">Total questions</span><span data-lang="bn" class="hidden">মোট প্রশ্ন</span></div></div>
          <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3"><div class="text-xl font-extrabold text-emerald-700">+<?= $fmt($pos) ?></div><div class="text-[11px] text-emerald-700"><span data-lang="en">Each correct answer</span><span data-lang="bn" class="hidden">প্রতিটি সঠিক উত্তর</span></div></div>
          <div class="rounded-xl bg-rose-50 border border-rose-200 p-3"><div class="text-xl font-extrabold text-rose-700">−<?= $fmt($neg) ?></div><div class="text-[11px] text-rose-700"><span data-lang="en">Each wrong answer</span><span data-lang="bn" class="hidden">প্রতিটি ভুল উত্তর</span></div></div>
        </div>
      </div>

      <div class="px-5 sm:px-7 py-6 space-y-7">
        <section>
          <h2 class="text-base font-bold text-slate-900 mb-2"><span data-lang="en">Sections, topics and number of questions</span><span data-lang="bn" class="hidden">বিভাগ, বিষয় ও প্রশ্নসংখ্যা</span></h2>
          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr>
                <th class="text-left px-3.5 py-2.5">#</th>
                <th class="text-left px-3.5 py-2.5"><span data-lang="en">Section</span><span data-lang="bn" class="hidden">বিভাগ</span></th>
                <th class="text-left px-3.5 py-2.5"><span data-lang="en">Topics covered</span><span data-lang="bn" class="hidden">বিষয়সমূহ</span></th>
                <th class="text-right px-3.5 py-2.5"><span data-lang="en">Questions</span><span data-lang="bn" class="hidden">প্রশ্ন</span></th>
                <th class="text-right px-3.5 py-2.5"><span data-lang="en">Marks</span><span data-lang="bn" class="hidden">নম্বর</span></th>
              </tr></thead>
              <tbody class="divide-y divide-slate-100">
              <?php foreach ($outline as $i => $o): ?>
                <tr class="align-top"><td class="px-3.5 py-2.5 text-slate-400"><?= $i + 1 ?></td>
                  <td class="px-3.5 py-2.5 font-semibold text-slate-900"><?= h($o['section']) ?></td>
                  <td class="px-3.5 py-2.5 text-slate-600 text-xs leading-relaxed"><?= h($o['topics']) ?></td>
                  <td class="px-3.5 py-2.5 text-right font-semibold"><?= (int) $o['n'] ?></td>
                  <td class="px-3.5 py-2.5 text-right text-slate-600"><?= $fmt((int) $o['n'] * $pos) ?></td></tr>
              <?php endforeach; ?>
              </tbody>
              <tfoot class="bg-red-50 font-bold text-slate-900"><tr><td></td><td class="px-3.5 py-2.5" colspan="2">Total</td><td class="px-3.5 py-2.5 text-right"><?= $totalQ ?></td><td class="px-3.5 py-2.5 text-right"><?= $fmt($maxMarks) ?></td></tr></tfoot>
            </table>
          </div>
        </section>

        <section data-lang="en">
          <h2 class="text-base font-bold text-slate-900 mb-2">General instructions</h2>
          <ol class="list-decimal pl-5 space-y-1.5 text-sm text-slate-700 leading-relaxed">
            <li>The total duration of the exam is <b><?= $dur ?> minutes</b>. The countdown timer at the top right of the screen shows the time left. When it reaches zero, the exam <b>submits automatically</b> — you do not need to submit it yourself.</li>
            <li>The test has <b><?= $totalQ ?> multiple-choice questions</b> with four options each; only <b>one</b> option is correct.</li>
            <li><b>Marking:</b> +<?= $fmt($pos) ?> for every correct answer<?= $neg > 0 ? ', and <b>−' . $fmt($neg) . '</b> for every wrong answer (negative marking)' : '. There is <b>no negative marking</b>' ?>. Questions you leave unanswered, or only mark for review without choosing an option, carry <b>no marks</b>.</li>
            <li>To answer, click an option. To change it, click another option. To remove your choice, click <b>Clear Response</b>.</li>
            <li>Click <b>Save &amp; Next</b> to save your answer and go to the next question. An answer is <b>not saved</b> until you click this button.</li>
            <li>Click <b>Mark for Review &amp; Next</b> to flag a question you want to revisit. <b>If you had chosen an option, a question marked for review is still evaluated;</b> if you had not chosen any option, it is treated as unanswered.</li>
            <li>You can jump to any question using the <b>question palette</b> on the right, or switch section with the section buttons at the top. Your answers stay saved when you move around.</li>
            <li>Use <b>View in: English | বাংলা</b> above each question to read it in either language at any time. Your choice stays for the next questions too.</li>
            <li>Some questions carry a diagram or chart; click it to enlarge.</li>
            <li>When you finish, click <b>Submit</b>. You will be shown a summary of answered, not answered and marked questions before you confirm. After submission you get your scorecard with solutions.</li>
            <li>Do not refresh, close the tab or press the browser's Back button during the exam. Your answers are autosaved, but the timer keeps running.</li>
          </ol>
        </section>
        <section data-lang="bn" class="hidden">
          <h2 class="text-base font-bold text-slate-900 mb-2">সাধারণ নির্দেশাবলি</h2>
          <ol class="list-decimal pl-5 space-y-1.5 text-sm text-slate-700 leading-relaxed">
            <li>পরীক্ষার মোট সময় <b><?= $dur ?> মিনিট</b>। পর্দার উপরে ডানদিকের কাউন্টডাউন টাইমারে বাকি সময় দেখা যাবে। সময় শেষ হলে পরীক্ষা <b>নিজে থেকেই জমা হয়ে যাবে</b>, আপনাকে আলাদা করে জমা দিতে হবে না।</li>
            <li>পরীক্ষায় মোট <b><?= $totalQ ?>টি বহু-বিকল্পভিত্তিক প্রশ্ন</b> আছে। প্রতিটি প্রশ্নের চারটি বিকল্পের মধ্যে মাত্র <b>একটি</b> সঠিক।</li>
            <li><b>নম্বর বিভাজন:</b> প্রতিটি সঠিক উত্তরের জন্য +<?= $fmt($pos) ?><?= $neg > 0 ? ' এবং প্রতিটি ভুল উত্তরের জন্য <b>−' . $fmt($neg) . '</b> (নেগেটিভ মার্কিং)' : '। এই পরীক্ষায় <b>নেগেটিভ মার্কিং নেই</b>' ?>। যে প্রশ্নের উত্তর দেননি, বা শুধু রিভিউয়ের জন্য চিহ্নিত করেছেন কিন্তু কোনো বিকল্প বাছেননি, তাতে <b>কোনো নম্বর নেই</b>।</li>
            <li>উত্তর দিতে একটি বিকল্পে ক্লিক করুন। বদলাতে হলে অন্য বিকল্পে ক্লিক করুন। বাছাই মুছতে <b>Clear Response</b>-এ ক্লিক করুন।</li>
            <li>উত্তর সংরক্ষণ করে পরের প্রশ্নে যেতে <b>Save &amp; Next</b>-এ ক্লিক করুন। এই বোতামে ক্লিক না করা পর্যন্ত উত্তর <b>সংরক্ষিত হয় না</b>।</li>
            <li>পরে আবার দেখতে চাইলে <b>Mark for Review &amp; Next</b>-এ ক্লিক করুন। <b>বিকল্প বাছা থাকলে রিভিউয়ের জন্য চিহ্নিত প্রশ্নও মূল্যায়নে ধরা হবে</b>; কোনো বিকল্প না বাছলে সেটি উত্তরহীন গণ্য হবে।</li>
            <li>ডানদিকের <b>প্রশ্ন-তালিকা (palette)</b> থেকে যেকোনো প্রশ্নে যাওয়া যায়, আর উপরের বিভাগ-বোতাম দিয়ে বিভাগ বদলানো যায়। এদিক-ওদিক গেলেও আপনার উত্তর সংরক্ষিত থাকে।</li>
            <li>প্রতিটি প্রশ্নের উপরে <b>View in: English | বাংলা</b> বোতামে যেকোনো সময় প্রশ্নটি দুই ভাষায় পড়তে পারবেন। আপনার বাছাই পরের প্রশ্নগুলোতেও বজায় থাকবে।</li>
            <li>কিছু প্রশ্নে চিত্র বা চার্ট থাকে; বড় করে দেখতে সেটিতে ক্লিক করুন।</li>
            <li>শেষ হলে <b>Submit</b>-এ ক্লিক করুন। নিশ্চিত করার আগে উত্তর দেওয়া, উত্তর না দেওয়া ও রিভিউ-চিহ্নিত প্রশ্নের সারসংক্ষেপ দেখানো হবে। জমা দেওয়ার পর সমাধানসহ স্কোরকার্ড পাবেন।</li>
            <li>পরীক্ষা চলাকালীন পেজ রিফ্রেশ করবেন না, ট্যাব বন্ধ করবেন না এবং ব্রাউজারের Back বোতাম চাপবেন না। উত্তর নিজে থেকে সংরক্ষিত হয়, কিন্তু টাইমার চলতেই থাকে।</li>
          </ol>
        </section>

        <section>
          <h2 class="text-base font-bold text-slate-900 mb-3"><span data-lang="en">Question palette — what the colours mean</span><span data-lang="bn" class="hidden">প্রশ্ন-তালিকার রঙের অর্থ</span></h2>
          <div class="grid sm:grid-cols-2 gap-3.5">
            <?= $legend('bg-slate-100 text-slate-700 border border-slate-300 rounded-md', '1', 'You have <b>not visited</b> the question yet.', 'আপনি প্রশ্নটি এখনও <b>দেখেননি</b>।') ?>
            <?= $legend('bg-rose-600 text-white rounded-b-xl rounded-t-sm', '2', 'You have visited the question but <b>not answered</b> it.', 'প্রশ্নটি দেখেছেন কিন্তু <b>উত্তর দেননি</b>।') ?>
            <?= $legend('bg-emerald-600 text-white rounded-t-xl rounded-b-sm', '3', 'You have <b>answered</b> the question (saved).', 'আপনি প্রশ্নটির <b>উত্তর দিয়েছেন</b> (সংরক্ষিত)।') ?>
            <?= $legend('bg-purple-600 text-white rounded-full', '4', '<b>Marked for review</b> without an answer — treated as unanswered.', 'উত্তর ছাড়াই <b>রিভিউয়ের জন্য চিহ্নিত</b> — উত্তরহীন গণ্য হবে।') ?>
            <?= $legend('bg-purple-600 text-white rounded-full', '5', '<b>Answered and marked for review</b> — the answer <b>will be evaluated</b>.', '<b>উত্তর দেওয়া ও রিভিউয়ের জন্য চিহ্নিত</b> — উত্তরটি <b>মূল্যায়নে ধরা হবে</b>।') ?>
          </div>
        </section>
      </div>

      <div class="px-5 sm:px-7 py-5 border-t border-red-100 bg-slate-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <label class="flex items-start gap-2.5 cursor-pointer text-sm text-slate-700 max-w-xl">
          <input id="ins-agree" type="checkbox" class="mt-0.5 w-4 h-4 accent-red-600 cursor-pointer">
          <span><span data-lang="en">I have read and understood all the instructions. I understand that the timer starts as soon as I begin and cannot be paused.</span><span data-lang="bn" class="hidden">আমি সব নির্দেশাবলি পড়েছি ও বুঝেছি। আমি জানি শুরু করার সাথে সাথেই টাইমার চালু হবে এবং তা থামানো যাবে না।</span></span>
        </label>
        <button type="button" id="ins-begin" disabled class="shrink-0 w-full sm:w-auto px-7 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/20 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
          <?= icon('play', 'w-4 h-4') ?><span>Okay, I am ready to begin</span>
        </button>
      </div>
    </div>
  </main>
</div>
<script>window.INS = <?= js_json(['attemptId' => (int) $attempt['id']]) ?>;</script>
<script src="assets/instructions.js"></script>
