-- ============================================================
-- EPS-TOPIK Content Fix Migration
-- Adds dialogue_text to listening_questions
-- Replaces all content with authentic Korean EPS-TOPIK format
-- ============================================================

-- ============================================================
-- 1. SCHEMA CHANGE: Add dialogue_text to listening_questions
-- ============================================================

ALTER TABLE listening_questions ADD COLUMN IF NOT EXISTS dialogue_text TEXT DEFAULT NULL AFTER audio_path;

-- ============================================================
-- 2. REPLACE READING PASSAGES WITH KOREAN-ONLY CONTENT
-- ============================================================

-- Clear existing reading questions first (foreign key constraint)
DELETE FROM reading_questions;
DELETE FROM reading_passages;

-- Passage 1: Factory Safety Notice (공지사항)
INSERT INTO reading_passages (id, category_id, title, passage_text, content_type, difficulty) VALUES
(1, 25, '공장 안전 수칙', '<div class="notice-board">
<h3 style="text-align:center; margin-bottom:12px;">⚠️ 안전 수칙</h3>
<ol>
<li>작업 시 반드시 안전모와 안전화를 착용하십시오.</li>
<li>기계를 만지기 전에 반드시 전원을 끄십시오.</li>
<li>비상구 위치를 미리 확인하십시오.</li>
<li>위험한 물질은 지정된 장소에 보관하십시오.</li>
<li>사고가 발생하면 즉시 관리자에게 보고하십시오.</li>
<li>작업 중에 휴대전화를 사용하지 마십시오.</li>
</ol>
</div>', 'notice', 'beginner');

-- Passage 2: No Food Sign (표지판)
INSERT INTO reading_passages (id, category_id, title, passage_text, content_type, difficulty) VALUES
(2, 26, '작업장 표지판', '<div class="sign-board" style="text-align:center; padding: 16px;">
<p style="font-size:1.5em; font-weight:bold;">🚫 음식물 반입 금지</p>
<p style="margin-top:8px;">작업장 안에 음식이나 음료수를 가지고 들어갈 수 없습니다.</p>
<p style="margin-top:4px;">음식은 휴게실에서만 드십시오.</p>
</div>', 'sign', 'beginner');

-- Passage 3: Work Schedule (일정표)
INSERT INTO reading_passages (id, category_id, title, passage_text, content_type, difficulty) VALUES
(3, 29, '이번 주 작업 일정', '<div class="schedule">
<h3 style="text-align:center; margin-bottom:12px;">📅 이번 주 작업 일정</h3>
<table style="width:100%; border-collapse:collapse; text-align:center;">
<tr style="background:#f1f5f9;"><th style="padding:8px; border:1px solid #e2e8f0;">요일</th><th style="padding:8px; border:1px solid #e2e8f0;">작업 내용</th><th style="padding:8px; border:1px solid #e2e8f0;">시간</th></tr>
<tr><td style="padding:8px; border:1px solid #e2e8f0;">월요일</td><td style="padding:8px; border:1px solid #e2e8f0;">기계 점검</td><td style="padding:8px; border:1px solid #e2e8f0;">09:00 ~ 12:00</td></tr>
<tr><td style="padding:8px; border:1px solid #e2e8f0;">화요일</td><td style="padding:8px; border:1px solid #e2e8f0;">생산 작업</td><td style="padding:8px; border:1px solid #e2e8f0;">08:00 ~ 17:00</td></tr>
<tr><td style="padding:8px; border:1px solid #e2e8f0;">수요일</td><td style="padding:8px; border:1px solid #e2e8f0;">안전 교육</td><td style="padding:8px; border:1px solid #e2e8f0;">14:00 ~ 16:00</td></tr>
<tr><td style="padding:8px; border:1px solid #e2e8f0;">목요일</td><td style="padding:8px; border:1px solid #e2e8f0;">생산 작업</td><td style="padding:8px; border:1px solid #e2e8f0;">08:00 ~ 17:00</td></tr>
<tr><td style="padding:8px; border:1px solid #e2e8f0;">금요일</td><td style="padding:8px; border:1px solid #e2e8f0;">정리 및 보고</td><td style="padding:8px; border:1px solid #e2e8f0;">08:00 ~ 15:00</td></tr>
</table>
</div>', 'schedule', 'beginner');

-- Passage 4: Worker's Daily Life (일상 글)
INSERT INTO reading_passages (id, category_id, title, passage_text, content_type, difficulty) VALUES
(4, 28, '김민수 씨의 하루', '<p>김민수 씨는 자동차 공장에서 일합니다. 매일 아침 7시에 일어나서 8시까지 공장에 갑니다. 오전에는 자동차 부품을 조립하고 오후에는 완성된 제품을 검사합니다.</p>
<p>점심은 12시부터 1시까지입니다. 점심은 공장 식당에서 먹습니다. 식당 음식은 무료입니다. 오후 5시에 퇴근합니다.</p>
<p>퇴근 후에는 기숙사에서 쉬거나 한국어를 공부합니다. 주말에는 친구들과 시장에 가거나 공원에서 운동합니다.</p>', 'passage', 'beginner');

-- Passage 5: Korean Class Notice (안내문)
INSERT INTO reading_passages (id, category_id, title, passage_text, content_type, difficulty) VALUES
(5, 25, '한국어 교실 안내', '<div class="notice-board">
<h3 style="text-align:center; margin-bottom:12px;">📚 한국어 교실 안내</h3>
<p><strong>일시:</strong> 매주 토요일 오전 10시 ~ 12시</p>
<p><strong>장소:</strong> 다문화가족지원센터 3층 교육실</p>
<p><strong>대상:</strong> 외국인 근로자</p>
<p><strong>비용:</strong> 무료</p>
<p><strong>준비물:</strong> 필기도구, 노트</p>
<p style="margin-top:8px;">※ 수업에 참석하려면 미리 전화로 신청하십시오.</p>
<p>전화: 031-123-4567</p>
</div>', 'notice', 'beginner');

-- Passage 6: Dormitory Rules (기숙사 규칙)
INSERT INTO reading_passages (id, category_id, title, passage_text, content_type, difficulty) VALUES
(6, 25, '기숙사 생활 규칙', '<div class="notice-board">
<h3 style="text-align:center; margin-bottom:12px;">🏠 기숙사 생활 규칙</h3>
<ol>
<li>밤 10시 이후에는 큰 소리로 이야기하거나 음악을 듣지 마십시오.</li>
<li>방을 깨끗하게 사용하십시오.</li>
<li>세탁기는 오전 7시부터 밤 9시까지 사용할 수 있습니다.</li>
<li>음식은 부엌에서만 만드십시오. 방에서 요리하지 마십시오.</li>
<li>외부인은 기숙사에 들어올 수 없습니다.</li>
<li>쓰레기는 분리수거 하십시오.</li>
</ol>
</div>', 'notice', 'intermediate');

-- Passage 7: Hospital Visit Dialogue (대화문)
INSERT INTO reading_passages (id, category_id, title, passage_text, content_type, difficulty) VALUES
(7, 28, '병원 방문', '<p><strong>의사:</strong> 어디가 아프세요?</p>
<p><strong>환자:</strong> 어제부터 머리가 아프고 열이 나요.</p>
<p><strong>의사:</strong> 기침도 하세요?</p>
<p><strong>환자:</strong> 네, 기침도 좀 해요. 그리고 목도 아파요.</p>
<p><strong>의사:</strong> 감기인 것 같습니다. 약을 처방해 드릴게요. 하루에 세 번, 식후에 드세요.</p>
<p><strong>환자:</strong> 네, 감사합니다. 며칠 쉬어야 해요?</p>
<p><strong>의사:</strong> 이틀 정도 쉬세요. 그리고 따뜻한 물을 많이 드세요.</p>', 'dialogue', 'intermediate');

-- Passage 8: Workplace Injury Notice (작업장 안내)
INSERT INTO reading_passages (id, category_id, title, passage_text, content_type, difficulty) VALUES
(8, 27, '산업재해 발생 시 행동 요령', '<div class="notice-board">
<h3 style="text-align:center; margin-bottom:12px;">🚨 산업재해 발생 시 행동 요령</h3>
<ol>
<li>다친 사람을 안전한 곳으로 옮기십시오.</li>
<li>심하게 다쳤을 때는 119에 전화하십시오.</li>
<li>가볍게 다쳤을 때는 응급 처치를 하십시오.</li>
<li>사고 발생 후 반드시 관리자에게 보고하십시오.</li>
<li>산업재해 보상을 받으려면 회사에 서류를 제출하십시오.</li>
</ol>
<p style="margin-top:8px;">※ 응급 처치 약품은 각 작업장 입구에 있습니다.</p>
</div>', 'instruction', 'intermediate');


-- ============================================================
-- 3. REPLACE READING QUESTIONS WITH KOREAN TEXT
-- ============================================================

-- Passage 1 Questions (Factory Safety Notice)
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES
(1, '기계를 만지기 전에 무엇을 해야 합니까?', '장갑을 끼다', '전원을 끄다', '관리자에게 물어보다', '설명서를 읽다', 'B', '안전 수칙 2번: "기계를 만지기 전에 반드시 전원을 끄십시오." (Rule #2: Turn off the power before touching machinery.)', 1),
(1, '사고가 발생하면 어떻게 해야 합니까?', '집에 가다', '경찰에 전화하다', '즉시 관리자에게 보고하다', '도움을 기다리다', 'C', '안전 수칙 5번: "사고가 발생하면 즉시 관리자에게 보고하십시오." (Rule #5: Report to the manager immediately.)', 2),
(1, '작업 중에 하면 안 되는 것은 무엇입니까?', '안전모를 쓰다', '안전화를 신다', '비상구를 확인하다', '휴대전화를 사용하다', 'D', '안전 수칙 6번: "작업 중에 휴대전화를 사용하지 마십시오." (Rule #6: Do not use cellphones during work.)', 3);

-- Passage 2 Questions (No Food Sign)
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES
(2, '작업장에서 할 수 없는 것은 무엇입니까?', '안전모를 쓰다', '음식을 먹다', '기계를 사용하다', '일을 하다', 'B', '표지판: "음식물 반입 금지" — 작업장에서 음식을 먹을 수 없습니다. (Sign: No food allowed — cannot eat in the work area.)', 1),
(2, '음식은 어디에서 먹을 수 있습니까?', '작업장', '사무실', '휴게실', '창고', 'C', '"음식은 휴게실에서만 드십시오." (Eat food only in the break room.)', 2);

-- Passage 3 Questions (Work Schedule)
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES
(3, '수요일에 무엇을 합니까?', '기계 점검', '생산 작업', '안전 교육', '정리 및 보고', 'C', '일정표: 수요일 — 안전 교육 (14:00~16:00) (Schedule: Wednesday — Safety training)', 1),
(3, '금요일 작업은 몇 시에 끝납니까?', '오후 3시', '오후 4시', '오후 5시', '오후 6시', 'A', '일정표: 금요일 08:00~15:00 → 오후 3시에 끝남 (Schedule: Friday ends at 3 PM)', 2),
(3, '생산 작업을 하는 요일은 언제입니까?', '월요일과 수요일', '화요일과 목요일', '수요일과 금요일', '월요일과 금요일', 'B', '일정표: 화요일과 목요일에 생산 작업 (Schedule: Production on Tuesday and Thursday)', 3);

-- Passage 4 Questions (Worker's Daily Life)
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES
(4, '김민수 씨는 어디에서 일합니까?', '식당', '병원', '자동차 공장', '학교', 'C', '"김민수 씨는 자동차 공장에서 일합니다." (Mr. Kim works at an automobile factory.)', 1),
(4, '김민수 씨는 점심을 어디에서 먹습니까?', '집', '식당 (밖)', '공장 식당', '편의점', 'C', '"점심은 공장 식당에서 먹습니다." (He eats lunch at the factory cafeteria.)', 2),
(4, '김민수 씨는 주말에 무엇을 합니까?', '야근을 하다', '한국어를 공부하다', '친구와 시장에 가거나 운동하다', '집에서 잠을 자다', 'C', '"주말에는 친구들과 시장에 가거나 공원에서 운동합니다." (On weekends, he goes to the market with friends or exercises.)', 3),
(4, '이 글의 내용과 같은 것은 무엇입니까?', '김민수 씨는 아침 6시에 출근합니다.', '공장 식당 음식은 돈을 내야 합니다.', '김민수 씨는 오후에 제품을 검사합니다.', '김민수 씨는 주말에도 일합니다.', 'C', '"오후에는 완성된 제품을 검사합니다." (In the afternoon, he inspects finished products.)', 4);

-- Passage 5 Questions (Korean Class Notice)
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES
(5, '한국어 수업은 언제 합니까?', '매주 월요일', '매주 수요일', '매주 금요일', '매주 토요일', 'D', '"일시: 매주 토요일 오전 10시~12시" (When: Every Saturday 10 AM-12 PM)', 1),
(5, '수업 비용은 얼마입니까?', '1만 원', '2만 원', '5만 원', '무료', 'D', '"비용: 무료" (Cost: Free)', 2),
(5, '수업에 참석하려면 어떻게 해야 합니까?', '인터넷으로 신청하다', '전화로 신청하다', '직접 방문하다', '이메일을 보내다', 'B', '"수업에 참석하려면 미리 전화로 신청하십시오." (To attend, apply by phone in advance.)', 3);

-- Passage 6 Questions (Dormitory Rules)
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES
(6, '밤 10시 이후에 하면 안 되는 것은 무엇입니까?', '세탁기를 사용하다', '큰 소리를 내다', '방에서 자다', '물을 마시다', 'B', '규칙 1번: "밤 10시 이후에는 큰 소리로 이야기하거나 음악을 듣지 마십시오." (After 10 PM, do not talk loudly or listen to music.)', 1),
(6, '음식은 어디에서 만들 수 있습니까?', '방', '부엌', '휴게실', '세탁실', 'B', '규칙 4번: "음식은 부엌에서만 만드십시오." (Cook food only in the kitchen.)', 2),
(6, '이 글의 내용과 다른 것은 무엇입니까?', '외부인은 기숙사에 들어올 수 없습니다.', '세탁기는 하루 종일 사용할 수 있습니다.', '쓰레기는 분리수거 해야 합니다.', '방을 깨끗하게 사용해야 합니다.', 'B', '규칙 3번: 세탁기는 오전 7시부터 밤 9시까지만 사용할 수 있습니다. 하루 종일이 아닙니다. (Washing machines: 7 AM to 9 PM only, not all day.)', 3);

-- Passage 7 Questions (Hospital Dialogue)
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES
(7, '환자는 어디가 아픕니까?', '배가 아프다', '다리가 아프다', '머리가 아프고 열이 난다', '허리가 아프다', 'C', '"어제부터 머리가 아프고 열이 나요." (Since yesterday, I have a headache and fever.)', 1),
(7, '의사는 환자에게 무엇을 하라고 했습니까?', '운동을 하다', '이틀 쉬고 따뜻한 물을 마시다', '입원하다', '다시 오다', 'B', '"이틀 정도 쉬세요. 그리고 따뜻한 물을 많이 드세요." (Rest for about 2 days and drink warm water.)', 2),
(7, '약은 하루에 몇 번 먹어야 합니까?', '한 번', '두 번', '세 번', '네 번', 'C', '"하루에 세 번, 식후에 드세요." (Take it 3 times a day, after meals.)', 3);

-- Passage 8 Questions (Workplace Injury Notice)
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES
(8, '심하게 다쳤을 때 어디에 전화해야 합니까?', '112', '119', '회사', '병원', 'B', '"심하게 다쳤을 때는 119에 전화하십시오." (Call 119 when seriously injured.)', 1),
(8, '응급 처치 약품은 어디에 있습니까?', '사무실', '식당', '각 작업장 입구', '주차장', 'C', '"응급 처치 약품은 각 작업장 입구에 있습니다." (First aid supplies are at the entrance of each work area.)', 2),
(8, '산업재해 보상을 받으려면 무엇을 해야 합니까?', '병원에 가다', '경찰에 신고하다', '회사에 서류를 제출하다', '보험회사에 전화하다', 'C', '"산업재해 보상을 받으려면 회사에 서류를 제출하십시오." (Submit documents to the company.)', 3);


-- ============================================================
-- 4. REPLACE LISTENING QUESTIONS WITH KOREAN DIALOGUES
-- ============================================================

DELETE FROM listening_questions;

INSERT INTO listening_questions (id, category_id, audio_path, dialogue_text, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty) VALUES
(1, 21, 'audio/listening/placeholder.mp3',
'남자: 저기요, 비상구가 어디에 있어요?\n여자: 저쪽 복도 끝에 있어요. 초록색 표지판이 보이시죠?\n남자: 아, 네. 감사합니다.',
'남자는 무엇을 찾고 있습니까?',
'화장실', '식당', '사무실', '비상구',
'D',
'남자가 "비상구가 어디에 있어요?"라고 물었습니다. (The man asked "Where is the emergency exit?")',
'beginner'),

(2, 21, 'audio/listening/placeholder.mp3',
'관리자: 이 구역에서는 안전모를 꼭 쓰세요.\n근로자: 네, 알겠습니다. 안전화도 신어야 해요?\n관리자: 네, 안전모와 안전화 둘 다 착용해야 합니다.',
'관리자는 근로자에게 무엇을 하라고 했습니까?',
'일찍 퇴근하다', '안전모를 쓰다', '휴식을 취하다', '기계를 청소하다',
'B',
'관리자가 "안전모를 꼭 쓰세요"라고 했습니다. (The manager said "Make sure to wear your safety helmet.")',
'beginner'),

(3, 22, 'audio/listening/placeholder.mp3',
'여자: 어서 오세요. 뭐 드시겠어요?\n남자: 김치찌개 하나 주세요.\n여자: 네, 음료수는 뭐로 하시겠어요?\n남자: 물 주세요.',
'이 대화는 어디에서 하고 있습니까?',
'식당', '병원', '은행', '버스 정류장',
'A',
'"뭐 드시겠어요?"는 식당에서 하는 말입니다. ("What would you like to eat?" is said at a restaurant.)',
'beginner'),

(4, 23, 'audio/listening/placeholder.mp3',
'안내 방송: 고객 여러분께 안내 말씀 드립니다. 저희 매장은 오늘 저녁 10시에 문을 닫습니다. 쇼핑을 서둘러 주시기 바랍니다. 감사합니다.',
'이 매장은 몇 시에 문을 닫습니까?',
'저녁 8시', '저녁 9시', '저녁 10시', '저녁 11시',
'C',
'안내 방송: "저희 매장은 오늘 저녁 10시에 문을 닫습니다." (Announcement: Our store closes at 10 PM today.)',
'beginner'),

(5, 23, 'audio/listening/placeholder.mp3',
'안내 방송: 직원 여러분께 알려 드립니다. 다음 주 수요일 오후 2시에 소방 훈련이 있습니다. 모든 직원은 반드시 참석하십시오. 훈련 시 주차장으로 대피하십시오.',
'안내 방송의 내용은 무엇입니까?',
'소방 훈련 일정', '공휴일 안내', '급여 인상', '신입 사원 소개',
'A',
'안내 방송에서 "소방 훈련이 있습니다"라고 했습니다. (The announcement said "There will be a fire drill.")',
'intermediate'),

(6, 22, 'audio/listening/placeholder.mp3',
'남자: 여보세요, 사장님. 저 박준호입니다.\n사장님: 네, 준호 씨. 무슨 일이에요?\n남자: 오늘 몸이 안 좋아서 회사에 못 갈 것 같습니다.\n사장님: 알겠어요. 푹 쉬세요. 내일 올 수 있어요?\n남자: 네, 내일은 갈 수 있을 것 같습니다.',
'남자는 왜 전화했습니까?',
'회의 일정을 바꾸려고', '아파서 못 간다고 말하려고', '휴가를 신청하려고', '퇴근 시간을 물어보려고',
'B',
'남자가 "몸이 안 좋아서 회사에 못 갈 것 같습니다"라고 했습니다. (The man said he can''t go to work because he''s not feeling well.)',
'intermediate'),

(7, 21, 'audio/listening/placeholder.mp3',
'관리자: 오늘 오후에 새로운 기계가 도착합니다.\n근로자: 어디에 설치합니까?\n관리자: 2번 작업장에 설치할 거예요. 오후 2시까지 자리를 비워 주세요.\n근로자: 네, 알겠습니다.',
'새로운 기계는 어디에 설치합니까?',
'1번 작업장', '2번 작업장', '3번 작업장', '사무실',
'B',
'관리자가 "2번 작업장에 설치할 거예요"라고 했습니다. (The manager said it will be installed in work area 2.)',
'beginner'),

(8, 22, 'audio/listening/placeholder.mp3',
'여자: 이번 주말에 뭐 할 거예요?\n남자: 친구하고 같이 부산에 갈 거예요.\n여자: 좋겠네요. 부산에서 뭐 할 거예요?\n남자: 해운대 해수욕장에 가려고요. 수영도 하고 회도 먹을 거예요.',
'남자는 주말에 어디에 갑니까?',
'서울', '제주도', '부산', '대구',
'C',
'남자가 "친구하고 같이 부산에 갈 거예요"라고 했습니다. (The man said he will go to Busan with a friend.)',
'beginner'),

(9, 24, 'audio/listening/placeholder.mp3',
'관리자: 이 기계를 사용할 때 주의할 점을 알려 드리겠습니다. 먼저 전원 버튼을 누르세요. 그다음에 재료를 넣으세요. 기계가 작동하는 동안 손을 넣지 마세요.',
'기계 사용 중에 하면 안 되는 것은 무엇입니까?',
'전원 버튼을 누르다', '재료를 넣다', '기계 안에 손을 넣다', '기계를 끄다',
'C',
'관리자가 "기계가 작동하는 동안 손을 넣지 마세요"라고 했습니다. (The manager said "Do not put your hands in while the machine is running.")',
'intermediate'),

(10, 22, 'audio/listening/placeholder.mp3',
'여자: 실례합니다. 이 근처에 약국이 있어요?\n남자: 네, 저기 편의점 옆에 있어요.\n여자: 걸어서 얼마나 걸려요?\n남자: 5분쯤 걸려요. 이 길을 따라 쭉 가시면 돼요.',
'여자는 어디를 찾고 있습니까?',
'병원', '편의점', '약국', '은행',
'C',
'여자가 "이 근처에 약국이 있어요?"라고 물었습니다. (The woman asked "Is there a pharmacy nearby?")',
'beginner');


-- ============================================================
-- 5. REPLACE MOCK EXAM QUESTIONS
-- ============================================================

DELETE FROM mock_exam_attempt_answers;
DELETE FROM mock_exam_attempts;
DELETE FROM mock_exam_questions;
DELETE FROM mock_exams;

INSERT INTO mock_exams (id, title, description, time_limit_minutes, listening_count, reading_count, total_score, passing_score) VALUES
(1, 'EPS-TOPIK 모의시험 1', '실제 EPS-TOPIK 시험과 같은 형식의 모의시험입니다. 듣기와 읽기 영역으로 구성되어 있습니다.', 70, 10, 10, 80, 32);

-- Mock Exam: Listening Section (10 questions with Korean dialogues)
INSERT INTO mock_exam_questions (exam_id, section, question_number, audio_path, passage_text, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, points) VALUES
(1, 'listening', 1, 'audio/exam/placeholder.mp3',
'여자: 화장실이 어디에 있어요?\n남자: 이쪽으로 오세요. 저기 왼쪽에 있어요.\n여자: 감사합니다.',
'여자는 무엇을 찾고 있습니까?',
'사무실', '식당', '화장실', '주차장',
'C', '여자가 "화장실이 어디에 있어요?"라고 물었습니다. (The woman asked "Where is the restroom?")', 4),

(1, 'listening', 2, 'audio/exam/placeholder.mp3',
'남자: 출근 시간이 몇 시예요?\n여자: 오전 8시에 출근해야 해요.\n남자: 그러면 몇 시에 퇴근해요?\n여자: 오후 5시에 퇴근해요.',
'출근 시간은 몇 시입니까?',
'오전 7시', '오전 8시', '오전 9시', '오전 10시',
'B', '여자가 "오전 8시에 출근해야 해요"라고 했습니다. (She said work starts at 8 AM.)', 4),

(1, 'listening', 3, 'audio/exam/placeholder.mp3',
'남자: 여보세요. 오늘 아파서 회사에 못 갑니다.\n여자: 많이 아파요? 병원에 가 보세요.\n남자: 네, 지금 가려고요. 내일은 출근할 수 있을 것 같아요.',
'남자는 왜 전화했습니까?',
'아파서 결근한다고 말하려고', '길을 물어보려고', '음식을 주문하려고', '회의 일정을 잡으려고',
'A', '남자가 "아파서 회사에 못 갑니다"라고 했습니다. (The man said he can''t go to work because he''s sick.)', 4),

(1, 'listening', 4, 'audio/exam/placeholder.mp3',
'안내 방송: 직원 여러분께 알려 드립니다. 내일 오후 2시에 소방 훈련이 있습니다. 훈련이 시작되면 주차장으로 대피해 주십시오. 모든 직원은 반드시 참석하십시오.',
'소방 훈련 시 직원들은 어디로 모여야 합니까?',
'사무실', '주차장', '정문', '식당',
'B', '안내 방송: "훈련이 시작되면 주차장으로 대피해 주십시오." (Evacuate to the parking lot when the drill starts.)', 4),

(1, 'listening', 5, 'audio/exam/placeholder.mp3',
'관리자: 김 씨, 공구함에서 공구 좀 가져다 주세요.\n근로자: 어떤 공구가 필요해요?\n관리자: 드라이버하고 렌치를 가져오세요.\n근로자: 네, 알겠습니다.',
'관리자는 근로자에게 무엇을 가져오라고 했습니까?',
'보고서', '안전 장비', '도시락', '공구',
'D', '관리자가 "공구 좀 가져다 주세요"라고 했습니다. (The manager asked to bring tools.)', 4),

(1, 'listening', 6, 'audio/exam/placeholder.mp3',
'여자: 이 물건은 어디에 놓을까요?\n남자: 3번 창고에 넣어 주세요.\n여자: 3번 창고가 어디에 있어요?\n남자: 저쪽 오른쪽에 있어요. 파란색 문이에요.',
'물건을 어디에 놓아야 합니까?',
'1번 창고', '2번 창고', '3번 창고', '4번 창고',
'C', '남자가 "3번 창고에 넣어 주세요"라고 했습니다. (The man said to put it in warehouse #3.)', 4),

(1, 'listening', 7, 'audio/exam/placeholder.mp3',
'남자: 오늘 야근해야 해요?\n여자: 네, 오늘 주문이 많아서 밤 9시까지 일해야 해요.\n남자: 야근 수당이 있어요?\n여자: 네, 야근 수당은 시간당 1.5배예요.',
'오늘 몇 시까지 일해야 합니까?',
'저녁 7시', '저녁 8시', '밤 9시', '밤 10시',
'C', '여자가 "밤 9시까지 일해야 해요"라고 했습니다. (She said they have to work until 9 PM.)', 4),

(1, 'listening', 8, 'audio/exam/placeholder.mp3',
'안내 방송: 다음 주 월요일은 한국의 공휴일입니다. 그래서 다음 주 월요일에는 쉽니다. 대신 다음 주 토요일에 출근하십시오.',
'다음 주 월요일에 왜 쉽니까?',
'회사 창립기념일이라서', '공휴일이라서', '기계 점검 때문에', '비가 와서',
'B', '안내 방송: "다음 주 월요일은 한국의 공휴일입니다." (Next Monday is a Korean national holiday.)', 4),

(1, 'listening', 9, 'audio/exam/placeholder.mp3',
'여자: 한국어를 어디에서 배워요?\n남자: 주말에 다문화센터에서 배워요.\n여자: 수업료가 있어요?\n남자: 아니요, 무료예요. 토요일 오전 10시에 수업이 있어요.',
'남자는 한국어를 어디에서 배웁니까?',
'학교', '회사', '다문화센터', '도서관',
'C', '남자가 "주말에 다문화센터에서 배워요"라고 했습니다. (The man said he learns at the multicultural center on weekends.)', 4),

(1, 'listening', 10, 'audio/exam/placeholder.mp3',
'관리자: 오늘 오후에 안전 교육이 있습니다.\n근로자: 몇 시에 시작해요?\n관리자: 오후 3시에 시작합니다. 2층 회의실로 오세요.\n근로자: 네, 알겠습니다.',
'안전 교육은 어디에서 합니까?',
'1층 식당', '2층 회의실', '3층 사무실', '작업장',
'B', '관리자가 "2층 회의실로 오세요"라고 했습니다. (The manager said "Come to the 2nd floor meeting room.")', 4);


-- Mock Exam: Reading Section (10 questions with Korean passages)
INSERT INTO mock_exam_questions (exam_id, section, question_number, passage_text, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, points) VALUES
(1, 'reading', 11, '공지사항\n내일(3월 15일) 오후 2시에 안전 교육이 있습니다.\n모든 직원은 반드시 참석하십시오.\n장소: 2층 회의실', '안전 교육은 언제 합니까?', '3월 14일 오후 2시', '3월 15일 오후 2시', '3월 15일 오후 3시', '3월 16일 오후 2시', 'B', '"내일(3월 15일) 오후 2시에 안전 교육이 있습니다." (Safety training on March 15 at 2 PM.)', 4),

(1, 'reading', 12, '식당 이용 안내\n점심시간: 12:00 ~ 13:00\n저녁시간: 18:00 ~ 19:00\n※ 음식을 밖으로 가져갈 수 없습니다.\n※ 식판은 사용 후 반납하십시오.', '식당에서 하면 안 되는 것은 무엇입니까?', '점심을 먹다', '음식을 밖으로 가져가다', '저녁을 먹다', '식판을 사용하다', 'B', '"음식을 밖으로 가져갈 수 없습니다." (Food cannot be taken outside.)', 4),

(1, 'reading', 13, '김민수 씨는 자동차 공장에서 일합니다. 매일 아침 8시에 출근해서 오후 5시에 퇴근합니다. 점심은 공장 식당에서 먹습니다. 식당 음식은 무료입니다.', '김민수 씨는 점심을 어디에서 먹습니까?', '집', '밖에 있는 식당', '공장 식당', '편의점', 'C', '"점심은 공장 식당에서 먹습니다." (He eats lunch at the factory cafeteria.)', 4),

(1, 'reading', 14, '⚠️ 이 구역은 관계자 외 출입 금지\n안전 장비를 착용하지 않으면 들어갈 수 없습니다.\n필요한 안전 장비: 안전모, 안전화, 보안경', '이 구역에 들어가려면 무엇이 필요합니까?', '신분증', '안전 장비', '관리자의 허가', '예약', 'B', '"안전 장비를 착용하지 않으면 들어갈 수 없습니다." (Cannot enter without safety equipment.)', 4),

(1, 'reading', 15, '한국어 수업 안내\n일시: 매주 토요일 오전 10시 ~ 12시\n장소: 다문화센터 3층\n대상: 외국인 근로자\n비용: 무료', '한국어 수업 비용은 얼마입니까?', '1만 원', '2만 원', '5만 원', '무료', 'D', '"비용: 무료" (Cost: Free)', 4),

(1, 'reading', 16, '택배 안내\n보내는 사람: 이영수\n받는 사람: 박민호\n물건: 책 2권\n배달 예정일: 7월 10일\n※ 부재 시 경비실에 맡겨 주세요.', '받는 사람이 없을 때 택배를 어디에 맡깁니까?', '우체국', '경비실', '이웃집', '편의점', 'B', '"부재 시 경비실에 맡겨 주세요." (If absent, leave it at the security office.)', 4),

(1, 'reading', 17, '저는 베트남에서 온 팜입니다. 한국에 온 지 6개월이 되었습니다. 처음에는 한국어를 몰라서 힘들었습니다. 지금은 간단한 한국어를 할 수 있습니다. 매주 토요일에 한국어를 배우고 있습니다. 한국어를 잘하면 일도 더 잘할 수 있을 것 같습니다.', '이 글의 내용과 같은 것은 무엇입니까?', '팜 씨는 한국에 온 지 1년이 되었습니다.', '팜 씨는 처음부터 한국어를 잘했습니다.', '팜 씨는 매주 토요일에 한국어를 배웁니다.', '팜 씨는 중국에서 왔습니다.', 'C', '"매주 토요일에 한국어를 배우고 있습니다." (He learns Korean every Saturday.)', 4),

(1, 'reading', 18, '공장 근무 일정\n주간 근무: 08:00 ~ 17:00 (월~금)\n야간 근무: 22:00 ~ 07:00 (월~금)\n※ 야간 근무 수당: 시간당 1.5배\n※ 주말 근무 시 추가 수당 지급', '야간 근무는 몇 시부터 몇 시까지입니까?', '오후 8시~오전 5시', '오후 10시~오전 7시', '오후 9시~오전 6시', '오후 11시~오전 8시', 'B', '"야간 근무: 22:00~07:00" 즉 오후 10시부터 오전 7시까지입니다. (Night shift: 10 PM to 7 AM)', 4),

(1, 'reading', 19, '건강 검진 안내\n일시: 4월 20일 오전 9시 ~ 12시\n장소: 1층 의무실\n대상: 전 직원\n※ 검진 전날 밤 9시 이후에는 음식을 먹지 마십시오.\n※ 당일 아침에 물도 마시지 마십시오.', '건강 검진 전에 하면 안 되는 것은 무엇입니까?', '잠을 자다', '전날 밤 9시 이후에 음식을 먹다', '회사에 오다', '옷을 입다', 'B', '"검진 전날 밤 9시 이후에는 음식을 먹지 마십시오." (Do not eat after 9 PM the night before.)', 4),

(1, 'reading', 20, '기숙사 세탁실 이용 안내\n이용 시간: 오전 7시 ~ 밤 9시\n이용 방법:\n1. 세탁기에 빨래를 넣으세요.\n2. 세제를 넣으세요.\n3. 시작 버튼을 누르세요.\n※ 다른 사람의 빨래를 꺼내지 마십시오.\n※ 세탁이 끝나면 바로 빨래를 꺼내십시오.', '세탁실은 언제 이용할 수 있습니까?', '하루 종일', '오전 7시~밤 9시', '오전 9시~밤 10시', '오전 8시~밤 8시', 'B', '"이용 시간: 오전 7시~밤 9시" (Usage hours: 7 AM to 9 PM)', 4);


-- ============================================================
-- 6. UPDATE QUIZ QUESTIONS TO INCLUDE SOME KOREAN
-- ============================================================

-- Keep quiz questions bilingual for learning purposes, but update
-- explanations to include Korean context
UPDATE quiz_questions SET explanation = '일하다 (ilhada)는 "to work"라는 뜻입니다.' WHERE quiz_id = 1 AND sort_order = 1;
UPDATE quiz_questions SET explanation = '망치 (mangchi)는 "hammer"라는 뜻입니다.' WHERE quiz_id = 1 AND sort_order = 2;
UPDATE quiz_questions SET explanation = '물 (mul)은 "water"라는 뜻입니다.' WHERE quiz_id = 1 AND sort_order = 3;
UPDATE quiz_questions SET explanation = '감사합니다 (gamsahamnida)는 "Thank you"라는 뜻입니다. 격식체입니다.' WHERE quiz_id = 1 AND sort_order = 4;
UPDATE quiz_questions SET explanation = '안전모 (anjeonmo)는 "safety helmet"이라는 뜻입니다.' WHERE quiz_id = 1 AND sort_order = 5;
UPDATE quiz_questions SET explanation = '위험 (wiheom)은 "danger"라는 뜻입니다.' WHERE quiz_id = 2 AND sort_order = 1;
UPDATE quiz_questions SET explanation = '비상구 (bisanggu)는 "emergency exit"이라는 뜻입니다.' WHERE quiz_id = 2 AND sort_order = 2;
UPDATE quiz_questions SET explanation = '소화기 (sohwagi)는 불을 끄는 데 사용하는 "fire extinguisher"입니다.' WHERE quiz_id = 2 AND sort_order = 3;
UPDATE quiz_questions SET explanation = '완전한 문장: "안전모를 꼭 쓰세요." (Make sure to wear your safety helmet.)' WHERE quiz_id = 2 AND sort_order = 4;
UPDATE quiz_questions SET explanation = '주의 (juui)는 "caution"이라는 뜻입니다. 조심하라는 의미입니다.' WHERE quiz_id = 2 AND sort_order = 5;
