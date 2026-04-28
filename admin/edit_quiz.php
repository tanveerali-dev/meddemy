<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$video_id = (int)($_GET['video_id'] ?? $_POST['video_id'] ?? 0);
if (!$video_id) { header("Location: manage_quizzes.php"); exit(); }

// Fetch video + course info
$vs = $conn->prepare("SELECT v.*, c.title as ctitle FROM video v JOIN course c ON c.course_id=v.course_id WHERE v.video_id=?");
$vs->bind_param("i",$video_id); $vs->execute();
$video = $vs->get_result()->fetch_assoc();
if (!$video) { header("Location: manage_quizzes.php"); exit(); }

// Fetch existing quiz
$qs = $conn->prepare("SELECT * FROM quiz WHERE video_id=?");
$qs->bind_param("i",$video_id); $qs->execute();
$quiz = $qs->get_result()->fetch_assoc();
$quiz_id = $quiz['quiz_id'] ?? null;

$msg = '';

/* ══ SAVE ══ */
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $qtitle = htmlspecialchars(trim($_POST['quiz_title'] ?: 'Quiz'));

    // Upsert quiz
    if ($quiz_id) {
        $upd = $conn->prepare("UPDATE quiz SET title=? WHERE quiz_id=?");
        $upd->bind_param("si",$qtitle,$quiz_id); $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO quiz (video_id,title) VALUES (?,?)");
        $ins->bind_param("is",$video_id,$qtitle); $ins->execute();
        $quiz_id = $conn->insert_id;
    }

    // Delete old questions then re-insert
    $conn->query("DELETE FROM question WHERE quiz_id=$quiz_id");

    $texts   = $_POST['q_text']    ?? [];
    $opts_a  = $_POST['q_opt_a']   ?? [];
    $opts_b  = $_POST['q_opt_b']   ?? [];
    $opts_c  = $_POST['q_opt_c']   ?? [];
    $opts_d  = $_POST['q_opt_d']   ?? [];
    $corrects= $_POST['q_correct'] ?? [];

    $saved = 0;
    foreach ($texts as $i => $text) {
        $text = htmlspecialchars(trim($text));
        if (!$text) continue;
        $a = htmlspecialchars(trim($opts_a[$i]  ?? ''));
        $b = htmlspecialchars(trim($opts_b[$i]  ?? ''));
        $c = htmlspecialchars(trim($opts_c[$i]  ?? ''));
        $d = htmlspecialchars(trim($opts_d[$i]  ?? ''));
        $correct = in_array($corrects[$i]??'',['a','b','c','d']) ? $corrects[$i] : 'a';
        if (!$a || !$b || !$c || !$d) continue;
        $si = $conn->prepare("INSERT INTO question (quiz_id,question_text,option_a,option_b,option_c,option_d,correct_opt) VALUES (?,?,?,?,?,?,?)");
        $si->bind_param("issssss",$quiz_id,$text,$a,$b,$c,$d,$correct);
        $si->execute(); $saved++;
    }

    $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Quiz saved with '.$saved.' question(s)!</div>';

    // Refresh quiz & questions
    $qs->execute(); $quiz = $qs->get_result()->fetch_assoc();
}

// Fetch questions
$questions = [];
if ($quiz_id) {
    $qres = $conn->query("SELECT * FROM question WHERE quiz_id=$quiz_id ORDER BY question_id ASC");
    while ($q=$qres->fetch_assoc()) $questions[]=$q;
}

$page_title = ($quiz_id ? 'Edit' : 'Add') . ' Quiz';
$active_nav = "manage_quizzes";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1><?php echo $quiz_id ? 'Edit' : 'Add'; ?> Quiz</h1>
        <p>
            <i class="fas fa-video" style="color:var(--gold);margin-right:5px;font-size:.8rem;"></i>
            <?php echo htmlspecialchars($video['title']); ?>
            <span style="color:var(--muted2);margin:0 6px;">·</span>
            <span style="color:var(--muted);"><?php echo htmlspecialchars($video['ctitle']); ?></span>
        </p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_quizzes.php" class="btn-adm btn-adm-ghost"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php echo $msg; ?>

<form method="POST" id="quizForm" class="adm-form">
    <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">

    <!-- Quiz Title -->
    <div class="adm-card" style="margin-bottom:20px;">
        <div class="adm-card-header"><h2>Quiz Settings</h2></div>
        <div class="adm-card-body">
            <div class="form-group" style="max-width:460px;margin-bottom:0;">
                <label class="form-label">Quiz Title</label>
                <input type="text" name="quiz_title" class="form-input"
                       value="<?php echo htmlspecialchars($quiz['title'] ?? 'Lecture Quiz'); ?>"
                       placeholder="e.g. Lecture 1 Quiz — Cell Biology">
            </div>
        </div>
    </div>

    <!-- Questions -->
    <div class="adm-card">
        <div class="adm-card-header">
            <h2><i class="fas fa-list-ul" style="color:var(--gold);margin-right:7px;font-size:.85rem;"></i>MCQ Questions</h2>
            <span class="adm-badge blue" id="qCountBadge"><?php echo count($questions); ?> questions</span>
        </div>
        <div class="adm-card-body">

            <div id="questionsContainer">
            <?php
            // Render existing questions OR one blank
            $toRender = !empty($questions) ? $questions : [null];
            foreach ($toRender as $idx => $q):
                $n = $idx; // 0-based index
            ?>
            <div class="q-block" id="qblock-<?php echo $n; ?>">
                <div class="q-block-header">
                    <span class="q-num">Q<?php echo $n+1; ?></span>
                    <button type="button" class="q-remove-btn" onclick="removeQuestion(this)" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="form-group">
                    <label class="form-label">Question <span>*</span></label>
                    <textarea name="q_text[]" class="form-textarea" rows="2"
                              placeholder="Write your question here..."
                              required><?php echo htmlspecialchars($q['question_text'] ?? ''); ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <?php foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $opt=>$label):
                    $val = $q['option_'.$opt] ?? '';
                    $isCorrect = isset($q['correct_opt']) && $q['correct_opt']===$opt;
                ?>
                <div class="opt-group <?php echo $isCorrect?'opt-correct':''; ?>" id="optgroup-<?php echo $n.'-'.$opt; ?>">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                        <div class="opt-label opt-<?php echo $opt; ?>"><?php echo $label; ?></div>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.75rem;color:var(--muted);user-select:none;">
                            <input type="radio" name="q_correct[<?php echo $n; ?>]" value="<?php echo $opt; ?>"
                                   <?php echo $isCorrect?'checked':''; ?>
                                   onchange="markCorrect(this,<?php echo $n; ?>)"
                                   style="accent-color:var(--green);">
                            Correct answer
                        </label>
                    </div>
                    <input type="text" name="q_opt_<?php echo $opt; ?>[]" class="form-input"
                           placeholder="Option <?php echo $label; ?>..."
                           value="<?php echo htmlspecialchars($val); ?>" required>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- Add question button -->
            <button type="button" class="btn-adm btn-adm-ghost" onclick="addQuestion()" style="margin-top:16px;">
                <i class="fas fa-plus"></i> Add Another Question
            </button>

        </div>
    </div>

    <!-- Save -->
    <div style="margin-top:20px;display:flex;gap:12px;">
        <button type="submit" class="btn-adm btn-adm-primary" style="min-width:160px;justify-content:center;">
            <i class="fas fa-save"></i> Save Quiz
        </button>
        <a href="manage_quizzes.php" class="btn-adm btn-adm-ghost">Cancel</a>
    </div>
</form>

<style>
.q-block{
    background:var(--s2);border:1px solid var(--border3);border-radius:14px;
    padding:20px;margin-bottom:14px;position:relative;
    animation:fadeIn .25s ease;
}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.q-block-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.q-num{font-family:'Playfair Display',serif;font-size:.95rem;font-weight:700;color:var(--gold);}
.q-remove-btn{background:rgba(255,77,77,.1);border:none;color:var(--red);width:28px;height:28px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;transition:.2s;}
.q-remove-btn:hover{background:rgba(255,77,77,.25);}
.opt-group{background:var(--s3);border:1px solid var(--border3);border-radius:10px;padding:12px;}
.opt-group.opt-correct{border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.05);}
.opt-label{width:24px;height:24px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0;}
.opt-a{background:rgba(255,215,0,.15);color:var(--gold);}
.opt-b{background:rgba(59,130,246,.15);color:var(--blue);}
.opt-c{background:rgba(249,115,22,.15);color:#f97316;}
.opt-d{background:rgba(168,85,247,.15);color:#a855f7;}
</style>

<script>
let qCount = <?php echo max(count($questions),1); ?>;

function addQuestion() {
    const n = qCount++;
    const html = `
    <div class="q-block" id="qblock-${n}">
        <div class="q-block-header">
            <span class="q-num">Q${document.querySelectorAll('.q-block').length+1}</span>
            <button type="button" class="q-remove-btn" onclick="removeQuestion(this)" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="form-group">
            <label class="form-label">Question <span>*</span></label>
            <textarea name="q_text[]" class="form-textarea" rows="2" placeholder="Write your question here..." required></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            ${['a','b','c','d'].map((opt,i)=>{
                const labels=['A','B','C','D'];
                const cls=['opt-a','opt-b','opt-c','opt-d'];
                return `<div class="opt-group" id="optgroup-${n}-${opt}">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                        <div class="opt-label ${cls[i]}">${labels[i]}</div>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.75rem;color:var(--muted);user-select:none;">
                            <input type="radio" name="q_correct[${n}]" value="${opt}"
                                   onchange="markCorrect(this,${n})"
                                   style="accent-color:var(--green);">
                            Correct answer
                        </label>
                    </div>
                    <input type="text" name="q_opt_${opt}[]" class="form-input"
                           placeholder="Option ${labels[i]}..." required>
                </div>`;
            }).join('')}
        </div>
    </div>`;
    document.getElementById('questionsContainer').insertAdjacentHTML('beforeend', html);
    renumberQuestions();
    updateCount();
}

function removeQuestion(btn) {
    const blocks = document.querySelectorAll('.q-block');
    if (blocks.length <= 1) { alert('At least one question is required.'); return; }
    btn.closest('.q-block').remove();
    renumberQuestions();
    updateCount();
}

function renumberQuestions() {
    document.querySelectorAll('.q-block').forEach((b,i) => {
        const num = b.querySelector('.q-num');
        if (num) num.textContent = 'Q'+(i+1);
    });
}

function markCorrect(radio, n) {
    ['a','b','c','d'].forEach(opt => {
        const el = document.getElementById('optgroup-'+n+'-'+opt);
        if (el) el.classList.remove('opt-correct');
    });
    const parent = radio.closest('.opt-group');
    if (parent) parent.classList.add('opt-correct');
}

function updateCount() {
    const c = document.querySelectorAll('.q-block').length;
    document.getElementById('qCountBadge').textContent = c+' question'+(c!==1?'s':'');
}
</script>

<?php include '_layout_end.php'; ?>