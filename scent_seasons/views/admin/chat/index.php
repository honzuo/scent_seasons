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
<style>

.chat-unread-badge {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ff3b30 0%, #ff2d55 100%);
    color: white;
    font-size: 10px;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    padding: 0 5px;
    box-shadow: 0 2px 8px rgba(255, 59, 48, 0.5);
    animation: badgePulse 2s ease-in-out infinite;
    flex-shrink: 0;
    margin-left: 8px;
}

@keyframes badgePulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 2px 8px rgba(255, 59, 48, 0.5);
    }
    50% {
        transform: scale(1.15);
        box-shadow: 0 4px 12px rgba(255, 59, 48, 0.7);
    }
}

.chat-unread-badge.large {
    font-size: 9px;
    min-width: 20px;
}


.chat-list-item.has-unread {
    background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
    border-left: 3px solid #ff3b30;
}

.chat-list-item.has-unread .name {
    font-weight: 700;
}

.chat-list-item.has-unread .email {
    color: #666;
}


.chat-list-item.active.has-unread {
    background: #0071e3;
    border-left: 3px solid #0071e3;
}

.chat-list-item.active .name {
    color: #fff;
}

.chat-list-item.active .email {
    color: rgba(255, 255, 255, 0.8);
}
</style>

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
    let memberListInterval = null;

    function renderMembers(members) {
        const list = $('#memberList');
        if (!members || members.length === 0) {
            list.html('<div class="chat-list-item muted">No conversations yet.</div>');
            return;
        }
        
        const wasSelected = currentMemberId;
        list.empty();
        
        members.forEach(function(m) {
            const item = $('<div></div>')
                .addClass('chat-list-item')
                .data('member-id', m.user_id);
            
            if (wasSelected === m.user_id) {
                item.addClass('active');
            }
            
            const nameDiv = $('<div></div>').addClass('name');
            const nameText = $('<span></span>').text(m.full_name || ('User #' + m.user_id));
            nameDiv.append(nameText);
            
            
            if (m.unread_count && m.unread_count > 0 && wasSelected !== m.user_id) {
                console.log('👉 Adding badge for', m.full_name, 'with', m.unread_count, 'unread');
                
                item.addClass('has-unread');
                
                const badge = $('<span></span>')
                    .addClass('chat-unread-badge')
                    .text(m.unread_count > 99 ? '99+' : m.unread_count);
                
                if (m.unread_count > 99) {
                    badge.addClass('large');
                }
                
                nameDiv.append(badge);
            }
            
            const emailDiv = $('<div></div>')
                .addClass('email')
                .text(m.email || '');
            
            item.append(nameDiv);
            item.append(emailDiv);
            
         
            item.on('click', function() {
                const clickedUserId = $(this).data('member-id');
                
                $('.chat-list-item').removeClass('active');
                $(this).addClass('active');
                
              
                $(this).removeClass('has-unread');
                $(this).find('.chat-unread-badge').fadeOut(150, function() {
                    $(this).remove();
                });
                
                currentMemberId = clickedUserId;
                $('#chatWith').text('Chat with ' + (m.full_name || ('User #' + clickedUserId)));
                $('#chatInput, #chatSend').prop('disabled', false);
                
                
                markAsRead(clickedUserId);
                
                fetchMessages();
                
                if (pollInterval) clearInterval(pollInterval);
                pollInterval = setInterval(fetchMessages, 3000);
            });
            
            list.append(item);
        });
        
        console.log('✅ Rendered', members.length, 'members');
    }

   
    function markAsRead(memberId) {
        console.log('🔵 Marking messages as read for user', memberId);
        
        $.ajax({
            url: '../../../controllers/chat_controller.php',
            method: 'POST',
            data: {
                action: 'mark_read',
                member_id: memberId
            },
            dataType: 'json',
            success: function(response) {
                console.log('✅ Mark as read response:', response);
                if (response.status === 'success') {
                    console.log('✅ Successfully marked', response.marked_count, 'messages as read');
                } else {
                    console.warn('⚠️ Mark as read failed:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Mark as read error:', status, error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    function fetchMembers() {
        $.getJSON('../../../controllers/chat_controller.php', { action: 'admin_list_members' }, function(res) {
            console.log('📥 Fetch members response:', res);
            if (res.status === 'success') {
                renderMembers(res.members);
            } else {
                console.error('❌ Failed to load members:', res);
                $('#memberList').html('<div class="chat-list-item muted">Failed to load members.</div>');
            }
        }).fail(function(xhr, status, error) {
            console.error('❌ Network error:', status, error);
            $('#memberList').html('<div class="chat-list-item muted">Error loading members.</div>');
        });
    }

    function renderMessages(messages) {
        const box = $('#chatMessages');
        const wasAtBottom = box[0].scrollHeight - box.scrollTop() <= box.outerHeight() + 50;
        
        box.empty();
        if (!messages || messages.length === 0) {
            box.html('<div class="muted">No messages yet. Say hello!</div>');
            return;
        }
        
        messages.forEach(function(msg) {
            const isAdmin = msg.is_admin == 1;
            const bubble = $('<div class="bubble"></div>');
            bubble.addClass(isAdmin ? 'admin' : 'member');
            
            const date = new Date(msg.created_at);
            const timeStr = date.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            
            const messageText = $('<div></div>').text(msg.message).html();
            bubble.html(messageText + '<span class="time">' + timeStr + '</span>');
            box.append(bubble);
        });
        
        if (wasAtBottom) {
            box.scrollTop(box[0].scrollHeight);
        }
    }

    function fetchMessages() {
        if (!currentMemberId) return;
        $.getJSON('../../../controllers/chat_controller.php', { 
            action: 'admin_fetch', 
            member_id: currentMemberId 
        }, function(res) {
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
        $.post('../../../controllers/chat_controller.php?action=admin_send', { 
            member_id: currentMemberId, 
            message: text 
        }, function(res) {
            $('#chatSend').prop('disabled', false);
            try { 
                if (typeof res === 'string') res = JSON.parse(res); 
            } catch (e) {}
            
            if (res && res.status === 'success') {
                $('#chatInput').val('');
                fetchMessages();
            } else {
                alert('Failed to send message');
            }
        }).fail(function() {
            $('#chatSend').prop('disabled', false);
            alert('Network error. Please try again.');
        });
    }


    $('#chatSend').on('click', sendMessage);
    $('#chatInput').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

  
    console.log('🚀 Initializing chat...');
    fetchMembers();
    
 
    memberListInterval = setInterval(function() {
        console.log('🔄 Auto-refreshing members...');
        fetchMembers();
    }, 10000);
    
  
    $(window).on('beforeunload', function() {
        if (pollInterval) clearInterval(pollInterval);
        if (memberListInterval) clearInterval(memberListInterval);
    });
})();
</script>

<?php require $path . 'includes/footer.php'; ?>