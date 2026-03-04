/* -------------------------------------------------------------------------- */
/* VIEW-ITEM.CSS - Item detail page (photo-first + claim request workflow)    */
/* Institutional + student-friendly service desk tone                         */
/* -------------------------------------------------------------------------- */

.view-shell{
  position: relative;
  padding: 40px 0 68px;
  background:
    radial-gradient(900px 420px at 50% -80px, rgba(155,44,44,0.10) 0%, rgba(155,44,44,0.00) 60%),
    linear-gradient(180deg, rgba(155,44,44,0.035) 0%, rgba(155,44,44,0.00) 260px),
    var(--bg-body);
}

.view-shell::before{
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 280px;
  background: linear-gradient(180deg, rgba(155,44,44,0.06) 0%, rgba(155,44,44,0.00) 100%);
  pointer-events: none;
}

.view-shell .container{
  position: relative;
  z-index: 1;
}

/* Top bar */
.view-topbar{
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  margin-bottom: 14px;
}

.back-link{
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--text-body);
  font-weight: 700;
}

.back-link:hover{
  color: var(--primary);
}

.back-ico{
  width: 36px;
  height: 36px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  border: 1px solid rgba(155,44,44,0.12);
  background: rgba(255,255,255,0.85);
  box-shadow: 0 10px 18px rgba(0,0,0,0.06);
}

.ico{
  width: 18px;
  height: 18px;
  color: rgba(155,44,44,0.70);
}

.status-stack{
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* Header */
.view-header{
  text-align: left;
  max-width: 90ch;
  margin: 0 0 18px;
}

.view-header h1{
  font-size: 2.0rem;
  letter-spacing: -0.02em;
  margin-bottom: 10px;
}

.view-subtext{
  color: var(--text-muted);
  max-width: 88ch;
}

/* Layout */
.view-layout{
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 18px;
  align-items: start;
}

/* Left media column */
.view-media{
  display: grid;
  gap: 12px;
}

.media-card{
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 18px;
  overflow: hidden;
  background: rgba(255,255,255,0.92);
  box-shadow: 0 14px 28px rgba(0,0,0,0.08);
}

/* Main Photo Wrapper for Hover Effect */
.main-photo-wrapper {
  position: relative;
  cursor: zoom-in;
}

.media-main{
  display: block;
  width: 100%;
  height: auto;
  max-height: 540px;
  object-fit: cover;
  background: #EDF2F7;
  transition: transform 0.3s ease;
}

/* Zoom hint overlay */
.zoom-hint {
  position: absolute;
  top: 16px;
  right: 16px;
  background: rgba(0, 0, 0, 0.5);
  color: white;
  padding: 8px;
  border-radius: 8px;
  opacity: 0;
  transition: opacity 0.2s ease;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.main-photo-wrapper:hover .zoom-hint {
  opacity: 1;
}

.media-thumbs{
  display: grid;
  grid-template-columns: repeat(4, 1fr); /* 4 columns for thumbnails */
  gap: 10px;
}

/* Square Thumbnails Fix */
.thumb{
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255,255,255,0.92);
  box-shadow: 0 10px 18px rgba(0,0,0,0.06);
  cursor: pointer;
  transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  padding: 0;
  
  /* Force square aspect ratio */
  aspect-ratio: 1 / 1;
  position: relative;
}

.thumb img{
  display: block;
  width: 100%;
  height: 100%; /* Fill the square container */
  object-fit: cover;
}

.thumb:hover{
  transform: translateY(-1px);
  box-shadow: 0 14px 24px rgba(0,0,0,0.10);
  border-color: rgba(155,44,44,0.18);
}

.thumb.is-active{
  border-color: rgba(155,44,44,0.5);
  box-shadow: 0 0 0 3px rgba(155,44,44,0.15);
}

/* Notice card */
.notice-card{
  display: grid;
  grid-template-columns: 40px 1fr;
  gap: 12px;
  align-items: start;

  border: 1px solid rgba(155,44,44,0.12);
  background: rgba(255,255,255,0.88);
  border-radius: 18px;
  padding: 14px;
  box-shadow: 0 14px 28px rgba(0,0,0,0.06);
  backdrop-filter: blur(8px);
}

.notice-ico{
  width: 40px;
  height: 40px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(155,44,44,0.06);
  border: 1px solid rgba(155,44,44,0.12);
}

.notice-text strong{
  display: block;
  margin-bottom: 4px;
}

.notice-text p{
  margin: 0;
  color: var(--text-muted);
  line-height: 1.55;
}

/* Right side */
.view-side{
  display: grid;
  gap: 12px;
}

.panel{
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 18px;
  padding: 16px;
  box-shadow: 0 14px 28px rgba(0,0,0,0.08);
}

.panel-title-row{
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.panel-title-row h2{
  font-size: 1.12rem;
  letter-spacing: -0.01em;
  margin: 0;
}

.helper{
  color: var(--text-muted);
  font-size: 0.9rem;
  font-weight: 600;
}

/* Facts */
.facts{
  display: grid;
  gap: 10px;
}

.fact{
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 10px;
  align-items: baseline;
}

.fact-label{
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(26,32,44,0.55);
  white-space: nowrap;
}

.fact-value{
  font-weight: 700;
  color: var(--text-body);
}

.divider{
  height: 1px;
  background: rgba(0,0,0,0.08);
  margin: 12px 0;
}

/* Notes */
.notes-label{
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(26,32,44,0.55);
  margin-bottom: 6px;
}

.notes-text{
  margin: 0;
  color: var(--text-body);
  line-height: 1.6;
}

/* Badge + pill (consistent with Find page) */
.badge{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 850;
  border: 1px solid rgba(0,0,0,0.08);
  letter-spacing: 0.03em;
}

.badge-recent{
  background: rgba(214,158,46,0.18);
  color: #744210;
  border-color: rgba(214,158,46,0.28);
}

.badge-pending{
  background: rgba(155,44,44,0.12);
  color: var(--primary);
  border-color: rgba(155,44,44,0.20);
}

.badge-claimed{
  background: rgba(43,108,176,0.10);
  color: #2B6CB0;
  border-color: rgba(43,108,176,0.18);
}

.pill{
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 750;
  color: var(--text-muted);
  background: rgba(255,255,255,0.90);
  border: 1px solid rgba(0,0,0,0.08);
}

/* Claim module */
.panel-claim .helper{
  text-align: right;
}

.claim-details{
  margin-top: 10px;
  border: 1px solid rgba(155,44,44,0.12);
  border-radius: 16px;
  background: rgba(155,44,44,0.04);
  overflow: hidden;
}

.claim-summary{
  list-style: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 12px;
  cursor: pointer;
}

.claim-summary::-webkit-details-marker{
  display: none;
}

.summary-left{
  display: flex;
  align-items: center;
  gap: 12px;
}

.summary-ico{
  width: 40px;
  height: 40px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(255,255,255,0.88);
  border: 1px solid rgba(155,44,44,0.14);
  box-shadow: 0 10px 18px rgba(0,0,0,0.06);
}

.summary-left strong{
  display: block;
  font-size: 0.98rem;
}

.summary-left small{
  display: block;
  margin-top: 2px;
  color: var(--text-muted);
  font-weight: 600;
}

.summary-arrow{
  width: 36px;
  height: 36px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(255,255,255,0.88);
  border: 1px solid rgba(0,0,0,0.08);
  transition: transform .16s ease;
}

.claim-details[open] .summary-arrow{
  transform: rotate(180deg);
}

/* Form */
.claim-form{
  padding: 14px 12px 12px;
  background: rgba(255,255,255,0.88);
  border-top: 1px solid rgba(155,44,44,0.12);
}

.form-grid{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.field{
  display: grid;
  gap: 6px;
  margin-bottom: 10px;
}

.field label{
  font-weight: 750;
  color: var(--text-main);
  font-size: 0.92rem;
}

.field input,
.field textarea{
  border: 1px solid rgba(0,0,0,0.10);
  border-radius: 12px;
  padding: 10px 12px;
  font: inherit;
  color: var(--text-main);
  background: #fff;
  outline: none;
}

.field input:focus,
.field textarea:focus{
  border-color: rgba(155,44,44,0.35);
  box-shadow: 0 0 0 3px rgba(155,44,44,0.12);
}

.field-hint{
  margin: 0;
  color: var(--text-muted);
  font-size: 0.86rem;
  line-height: 1.45;
}

.claim-actions{
  display: flex;
  gap: 10px;
  align-items: center;
  margin-top: 6px;
}

.fineprint{
  margin-top: 10px;
  color: var(--text-muted);
  font-size: 0.86rem;
  line-height: 1.45;
}

/* -------------------------------------------------------------------------- */
/* LIGHTBOX MODAL STYLES                                                      */
/* -------------------------------------------------------------------------- */

.lightbox-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.9);
  /* 10,000 ensures it sits over any sticky navbar (usually 100-1000) */
  z-index: 10000;
  display: flex;
  justify-content: center;
  align-items: center;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease;
}

.lightbox-overlay.active {
  opacity: 1;
  visibility: visible;
}

.lightbox-content {
  position: relative;
  max-width: 90%;
  max-height: 90%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.lightbox-content img {
  display: block;
  max-width: 100%;
  max-height: 90vh;
  border-radius: 4px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.5);
  object-fit: contain;
}

/* Close Button (Fixed to top right) */
.lightbox-close {
  position: absolute;
  top: 20px;
  right: 20px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: none;
  font-size: 2rem;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: background 0.2s;
  z-index: 10002;
}

.lightbox-close:hover {
  background: rgba(255, 255, 255, 0.4);
}

/* Floating Navigation Buttons (Next/Prev) */
.lightbox-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.1); /* Subtle background */
  color: white;
  border: none;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: all 0.2s ease;
  z-index: 10001; /* Above image, below close button */
}

.lightbox-nav:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-50%) scale(1.1);
}

.lightbox-nav.prev {
  left: 20px;
}

.lightbox-nav.next {
  right: 20px;
}

/* Responsive */
@media (max-width: 1024px){
  .view-layout{
    grid-template-columns: 1fr;
  }
  .panel-claim .helper{
    text-align: left;
  }
}

@media (max-width: 768px){
  .view-shell{ padding: 30px 0 56px; }
  .media-main{ max-height: 420px; }
  .form-grid{ grid-template-columns: 1fr; }
  
  .lightbox-close {
    top: 10px;
    right: 10px;
    width: 40px;
    height: 40px;
    font-size: 1.5rem;
  }
  
  /* On mobile, make nav buttons smaller and more transparent to avoid blocking view */
  .lightbox-nav {
    width: 44px;
    height: 44px;
    background: rgba(0,0,0,0.2); /* Darker for contrast on light images */
  }
  
  .lightbox-nav.prev { left: 10px; }
  .lightbox-nav.next { right: 10px; }
}