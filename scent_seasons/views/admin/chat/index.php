<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

$page_title = "Member Chat";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo $path; ?>css/Adminchat.css">

<h2>Member Chat</h2>
<p class="muted">Select a member to view the conversation and reply in real time.</p>

<div class="chat-layout">
    <div class="chat-panel">
        <h3>Members</h3>
        <div id="memberList" class="chat-list">
            <div class="chat-list-item muted">Loading members...</div>
        </div>
    </div>

    <div class="chat-panel">
        <div class="chat-window">
            <div id="chatWith" style="padding: 16px; border-bottom: 1px solid #e5e5e7; font-weight: 600;">No member selected</div>
            <div id="chatMessages" class="chat-messages">
                <div class="muted">Select a member to start chatting.</div>
            </div>
            <div class="chat-input">
                <textarea id="chatInput" placeholder="Type a message..." disabled></textarea>
                <button id="chatSend" disabled>Send</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    let currentMemberId = null;
    let pollInterval = null;

    function renderMembers(members) {
        const list = $('#memberList');
        if (!members || members.length === 0) {
            list.html('<div class="chat-list-item muted">No conversations yet.</div>');
            return;
        }
        list.empty();
        members.forEach(function(m) {
            const item = $('<div class="chat-list-item"></div>');
            item.data('member-id', m.user_id);
            item.append('<div class="name">' + (m.full_name || ('User #' + m.user_id)) + '</div>');
            item.append('<div class="email">' + (m.email || '') + '</div>');
            item.on('click', function() {
                $('.chat-list-item').removeClass('active');
                $(this).addClass('active');
                currentMemberId = m.user_id;
                $('#chatWith').text('Chat with ' + (m.full_name || ('User #' + m.user_id)));
                $('#chatInput, #chatSend').prop('disabled', false);
                fetchMessages();
                if (pollInterval) clearInterval(pollInterval);
                pollInterval = setInterval(fetchMessages, 5000);
            });
            list.append(item);
        });
    }

    function fetchMembers() {
        $.getJSON('../../../controllers/chat_controller.php', { action: 'admin_list_members' }, function(res) {
            if (res.status === 'success') {
                renderMembers(res.members);
            } else {
                $('#memberList').html('<div class="chat-list-item muted">Failed to load members.</div>');
            }
        });
    }

    function renderMessages(messages) {
        const box = $('#chatMessages');
        box.empty();
        if (!messages || messages.length === 0) {
            box.html('<div class="muted">No messages yet. Say hello!</div>');
            return;
        }
        messages.forEach(function(msg) {
            const isAdmin = msg.is_admin == 1;
            const bubble = $('<div class="bubble"></div>');
            bubble.addClass(isAdmin ? 'admin' : 'member');
            bubble.html($('<div/>').text(msg.message).html() + '<span class="time">' + msg.created_at + '</span>');
            box.append(bubble);
        });
        box.scrollTop(box[0].scrollHeight);
    }

    function fetchMessages() {
        if (!currentMemberId) return;
        $.getJSON('../../../controllers/chat_controller.php', { action: 'admin_fetch', member_id: currentMemberId }, function(res) {
            if (res.status === 'success') {
                renderMessages(res.messages);
            }
        });
    }

    function sendMessage() {
        if (!currentMemberId) return;
        const text = $('#chatInput').val().trim();
        if (!text) return;
        $('#chatSend').prop('disabled', true);
        $.post('../../../controllers/chat_controller.php?action=admin_send', { member_id: currentMemberId, message: text }, function(res) {
            $('#chatSend').prop('disabled', false);
            try { res = JSON.parse(res); } catch (e) {}
            if (res.status === 'success') {
                $('#chatInput').val('');
                fetchMessages();
            }
        });
    }

    $('#chatSend').on('click', sendMessage);
    $('#chatInput').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    fetchMembers();
})();
</script>

<?php require $path . 'includes/footer.php'; ?>

