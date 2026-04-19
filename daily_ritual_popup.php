<?php
// daily_ritual_popup.php - Daily ritual modal
?>
<!-- Daily Ritual Modal -->
<div class="modal fade" id="dailyRitualModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #11161f; border: 1px solid #1e293b; border-radius: 20px;">
            <div class="modal-header" style="border-bottom: 1px solid #1e293b;">
                <h5 class="modal-title" style="color: white;">
                    <i class="fas fa-sun" style="color: #f59e0b;"></i>
                    Daily Ritual - <?php echo date('F j, Y'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <!-- Question 1 -->
                <div style="margin-bottom: 20px; padding: 15px; background: #0f172a; border-radius: 12px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 35px; height: 35px; background: rgba(59,130,246,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-chart-line" style="color: #3b82f6;"></i>
                        </div>
                        <div style="flex: 1;">
                            <p style="color: white; font-weight: 600; margin-bottom: 5px;">Pre-market Analysis</p>
                            <p style="color: #94a3b8; font-size: 13px;">Did you complete your pre-market analysis?</p>
                            <div style="display: flex; gap: 20px; margin-top: 10px;">
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="pre_market" value="1" class="ritual-radio"> <span style="color: white;">Yes</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="pre_market" value="0" class="ritual-radio"> <span style="color: white;">No</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Question 2 -->
                <div style="margin-bottom: 20px; padding: 15px; background: #0f172a; border-radius: 12px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 35px; height: 35px; background: rgba(16,185,129,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-bed" style="color: #10b981;"></i>
                        </div>
                        <div style="flex: 1;">
                            <p style="color: white; font-weight: 600; margin-bottom: 5px;">Sleep Quality</p>
                            <p style="color: #94a3b8; font-size: 13px;">Did you sleep well and feel mentally clear?</p>
                            <div style="display: flex; gap: 20px; margin-top: 10px;">
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="slept_well" value="1" class="ritual-radio"> <span style="color: white;">Yes</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="slept_well" value="0" class="ritual-radio"> <span style="color: white;">No</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Question 3 -->
                <div style="margin-bottom: 20px; padding: 15px; background: #0f172a; border-radius: 12px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 35px; height: 35px; background: rgba(139,92,246,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-brain" style="color: #8b5cf6;"></i>
                        </div>
                        <div style="flex: 1;">
                            <p style="color: white; font-weight: 600; margin-bottom: 5px;">Mental Readiness</p>
                            <p style="color: #94a3b8; font-size: 13px;">Are you ready to accept both wins and losses today?</p>
                            <div style="display: flex; gap: 20px; margin-top: 10px;">
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="mentally_ready" value="1" class="ritual-radio"> <span style="color: white;">Yes</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="mentally_ready" value="0" class="ritual-radio"> <span style="color: white;">No</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Question 4 -->
                <div style="margin-bottom: 20px; padding: 15px; background: #0f172a; border-radius: 12px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 35px; height: 35px; background: rgba(245,158,11,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-shield-alt" style="color: #f59e0b;"></i>
                        </div>
                        <div style="flex: 1;">
                            <p style="color: white; font-weight: 600; margin-bottom: 5px;">Risk Acceptance</p>
                            <p style="color: #94a3b8; font-size: 13px;">Have you accepted the risk and will follow your plan?</p>
                            <div style="display: flex; gap: 20px; margin-top: 10px;">
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="accepted_risk" value="1" class="ritual-radio"> <span style="color: white;">Yes</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="accepted_risk" value="0" class="ritual-radio"> <span style="color: white;">No</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Readiness Score -->
                <div style="background: #0f172a; padding: 15px; border-radius: 12px; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #94a3b8;">Readiness Score</span>
                        <span id="readinessScore" style="font-size: 24px; font-weight: bold; color: #10b981;">0%</span>
                    </div>
                    <div style="height: 8px; background: #1e293b; border-radius: 4px; overflow: hidden;">
                        <div id="readinessProgress" style="height: 100%; width: 0%; background: linear-gradient(90deg, #3b82f6, #8b5cf6);"></div>
                    </div>
                    <div id="readinessWarning" style="display: none; margin-top: 10px; color: #ef4444; font-size: 12px;">
                        <i class="fas fa-exclamation-triangle"></i> Low readiness - consider not trading today
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #1e293b;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Skip</button>
                <button type="button" class="btn btn-success" onclick="completeRitual()">Complete Ritual</button>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate readiness score
document.querySelectorAll('.ritual-radio').forEach(radio => {
    radio.addEventListener('change', calculateReadiness);
});

function calculateReadiness() {
    let preMarket = document.querySelector('input[name="pre_market"]:checked')?.value;
    let sleptWell = document.querySelector('input[name="slept_well"]:checked')?.value;
    let mentallyReady = document.querySelector('input[name="mentally_ready"]:checked')?.value;
    let acceptedRisk = document.querySelector('input[name="accepted_risk"]:checked')?.value;
    
    let total = 0;
    let count = 0;
    
    if (preMarket !== undefined) { total += parseInt(preMarket); count++; }
    if (sleptWell !== undefined) { total += parseInt(sleptWell); count++; }
    if (mentallyReady !== undefined) { total += parseInt(mentallyReady); count++; }
    if (acceptedRisk !== undefined) { total += parseInt(acceptedRisk); count++; }
    
    let score = count > 0 ? Math.round((total / count) * 100) : 0;
    
    document.getElementById('readinessScore').textContent = score + '%';
    document.getElementById('readinessProgress').style.width = score + '%';
    
    if (score < 75) {
        document.getElementById('readinessWarning').style.display = 'block';
        document.getElementById('readinessScore').style.color = '#ef4444';
    } else {
        document.getElementById('readinessWarning').style.display = 'none';
        document.getElementById('readinessScore').style.color = '#10b981';
    }
}

function completeRitual() {
    let preMarket = document.querySelector('input[name="pre_market"]:checked')?.value;
    let sleptWell = document.querySelector('input[name="slept_well"]:checked')?.value;
    let mentallyReady = document.querySelector('input[name="mentally_ready"]:checked')?.value;
    let acceptedRisk = document.querySelector('input[name="accepted_risk"]:checked')?.value;
    
    if (preMarket === undefined || sleptWell === undefined || mentallyReady === undefined || acceptedRisk === undefined) {
        alert('Please answer all questions');
        return;
    }
    
    let readiness = Math.round((parseInt(preMarket) + parseInt(sleptWell) + parseInt(mentallyReady) + parseInt(acceptedRisk)) / 4 * 100);
    
    $.ajax({
        url: 'save_ritual.php',
        method: 'POST',
        data: {
            pre_market: preMarket,
            slept_well: sleptWell,
            mentally_ready: mentallyReady,
            accepted_risk: acceptedRisk,
            readiness: readiness,
            completed: 1
        },
        success: function(response) {
            if (response.success) {
                $('#dailyRitualModal').modal('hide');
                location.reload();
            }
        }
    });
}

function showDailyRitual() {
    $('#dailyRitualModal').modal('show');
}
</script>