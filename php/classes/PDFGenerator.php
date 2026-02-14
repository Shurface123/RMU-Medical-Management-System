<?php
// ===================================
// PDF GENERATOR CLASS
// Generates PDF documents for medical records
// ===================================

// Note: This uses TCPDF library. Install via: composer require tecnickcom/tcpdf
// Or download from: https://tcpdf.org/

require_once __DIR__ . '/../vendor/autoload.php';

use TCPDF;

class PDFGenerator {
    private $conn;
    private $pdf;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Generate lab result PDF
     */
    public function generateLabResultPDF($resultId, $outputPath = null) {
        // Get lab result data
        $query = "SELECT 
                    lr.*,
                    p.P_Name as patient_name,
                    p.P_Email as patient_email,
                    p.P_Phone as patient_phone,
                    t.test_name,
                    t.description as test_description,
                    d.D_Name as doctor_name
                  FROM lab_results lr
                  JOIN patients p ON lr.patient_id = p.P_ID
                  JOIN tests t ON lr.test_id = t.test_id
                  LEFT JOIN doctors d ON lr.doctor_id = d.D_ID
                  WHERE lr.result_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $resultId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Lab result not found'];
        }
        
        $data = $result->fetch_assoc();
        
        // Create PDF
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        // Set document information
        $this->pdf->SetCreator('RMU Medical Sickbay');
        $this->pdf->SetAuthor('RMU Medical Sickbay');
        $this->pdf->SetTitle('Lab Result - ' . $data['patient_name']);
        $this->pdf->SetSubject('Laboratory Test Result');
        
        // Remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Set margins
        $this->pdf->SetMargins(15, 15, 15);
        
        // Add page
        $this->pdf->AddPage();
        
        // Set font
        $this->pdf->SetFont('helvetica', '', 10);
        
        // Header
        $this->addLabResultHeader();
        
        // Patient Information
        $this->addPatientInfo($data);
        
        // Test Information
        $this->addTestInfo($data);
        
        // Results
        $this->addTestResults($data);
        
        // Interpretation
        if ($data['interpretation']) {
            $this->addInterpretation($data['interpretation']);
        }
        
        // Doctor's Notes
        if ($data['doctor_notes']) {
            $this->addDoctorNotes($data['doctor_notes']);
        }
        
        // Footer
        $this->addLabResultFooter();
        
        // Output
        if ($outputPath) {
            $this->pdf->Output($outputPath, 'F');
            return ['success' => true, 'path' => $outputPath];
        } else {
            $filename = 'lab_result_' . $resultId . '_' . date('Ymd') . '.pdf';
            $this->pdf->Output($filename, 'D');
            return ['success' => true];
        }
    }
    
    /**
     * Add lab result header
     */
    private function addLabResultHeader() {
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->SetTextColor(22, 160, 133);
        $this->pdf->Cell(0, 10, 'RMU MEDICAL SICKBAY', 0, 1, 'C');
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 5, 'Regional Maritime University, Accra, Ghana', 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Phone: 0502371207 | Email: Sickbay.txt@rmu.edu.gh', 0, 1, 'C');
        
        $this->pdf->Ln(5);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->SetDrawColor(22, 160, 133);
        $this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
        $this->pdf->Ln(10);
        
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 8, 'LABORATORY TEST RESULT', 0, 1, 'C');
        $this->pdf->Ln(5);
    }
    
    /**
     * Add patient information
     */
    private function addPatientInfo($data) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(0, 7, 'Patient Information', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(50, 6, 'Name:', 0, 0);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, $data['patient_name'], 0, 1);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(50, 6, 'Email:', 0, 0);
        $this->pdf->Cell(0, 6, $data['patient_email'], 0, 1);
        
        $this->pdf->Cell(50, 6, 'Phone:', 0, 0);
        $this->pdf->Cell(0, 6, $data['patient_phone'], 0, 1);
        
        $this->pdf->Ln(5);
    }
    
    /**
     * Add test information
     */
    private function addTestInfo($data) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(0, 7, 'Test Information', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(50, 6, 'Test Name:', 0, 0);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, $data['test_name'], 0, 1);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(50, 6, 'Test Date:', 0, 0);
        $this->pdf->Cell(0, 6, date('F j, Y', strtotime($data['test_date'])), 0, 1);
        
        $this->pdf->Cell(50, 6, 'Result Date:', 0, 0);
        $this->pdf->Cell(0, 6, date('F j, Y', strtotime($data['result_date'])), 0, 1);
        
        $this->pdf->Cell(50, 6, 'Status:', 0, 0);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetTextColor(39, 174, 96);
        $this->pdf->Cell(0, 6, $data['status'], 0, 1);
        $this->pdf->SetTextColor(0, 0, 0);
        
        if ($data['doctor_name']) {
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(50, 6, 'Ordered by:', 0, 0);
            $this->pdf->Cell(0, 6, $data['doctor_name'], 0, 1);
        }
        
        $this->pdf->Ln(5);
    }
    
    /**
     * Add test results
     */
    private function addTestResults($data) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(0, 7, 'Test Results', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        
        $results = json_decode($data['results'], true);
        $normalRanges = json_decode($data['normal_range'], true);
        
        if ($results && is_array($results)) {
            // Table header
            $this->pdf->SetFont('helvetica', 'B', 9);
            $this->pdf->SetFillColor(22, 160, 133);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell(60, 7, 'Parameter', 1, 0, 'L', true);
            $this->pdf->Cell(40, 7, 'Result', 1, 0, 'C', true);
            $this->pdf->Cell(40, 7, 'Normal Range', 1, 0, 'C', true);
            $this->pdf->Cell(40, 7, 'Status', 1, 1, 'C', true);
            
            // Table data
            $this->pdf->SetFont('helvetica', '', 9);
            $this->pdf->SetTextColor(0, 0, 0);
            
            foreach ($results as $param => $value) {
                $normalRange = $normalRanges[$param] ?? 'N/A';
                $status = $this->getResultStatus($value, $normalRange);
                
                $this->pdf->Cell(60, 6, $param, 1, 0, 'L');
                $this->pdf->Cell(40, 6, $value, 1, 0, 'C');
                $this->pdf->Cell(40, 6, $normalRange, 1, 0, 'C');
                
                // Status with color
                if ($status === 'Normal') {
                    $this->pdf->SetTextColor(39, 174, 96);
                } elseif ($status === 'High' || $status === 'Low') {
                    $this->pdf->SetTextColor(231, 76, 60);
                }
                
                $this->pdf->Cell(40, 6, $status, 1, 1, 'C');
                $this->pdf->SetTextColor(0, 0, 0);
            }
        }
        
        $this->pdf->Ln(5);
    }
    
    /**
     * Get result status
     */
    private function getResultStatus($value, $normalRange) {
        if ($normalRange === 'N/A') {
            return 'N/A';
        }
        
        // Parse normal range (e.g., "10-20", "<5", ">100")
        if (preg_match('/^(\d+\.?\d*)\s*-\s*(\d+\.?\d*)$/', $normalRange, $matches)) {
            $min = floatval($matches[1]);
            $max = floatval($matches[2]);
            $val = floatval($value);
            
            if ($val < $min) return 'Low';
            if ($val > $max) return 'High';
            return 'Normal';
        }
        
        return 'N/A';
    }
    
    /**
     * Add interpretation
     */
    private function addInterpretation($interpretation) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(0, 7, 'Interpretation', 0, 1, 'L', true);
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->MultiCell(0, 5, $interpretation, 0, 'L');
        $this->pdf->Ln(5);
    }
    
    /**
     * Add doctor's notes
     */
    private function addDoctorNotes($notes) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(0, 7, "Doctor's Notes", 0, 1, 'L', true);
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->MultiCell(0, 5, $notes, 0, 'L');
        $this->pdf->Ln(5);
    }
    
    /**
     * Add footer
     */
    private function addLabResultFooter() {
        $this->pdf->SetY(-30);
        $this->pdf->SetFont('helvetica', 'I', 8);
        $this->pdf->SetTextColor(128, 128, 128);
        
        $this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
        $this->pdf->Ln(2);
        
        $this->pdf->Cell(0, 5, 'This is a computer-generated document. No signature is required.', 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Generated on: ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'For questions, contact: Sickbay.txt@rmu.edu.gh | 0502371207', 0, 1, 'C');
    }
    
    /**
     * Generate prescription PDF
     */
    public function generatePrescriptionPDF($prescriptionId, $outputPath = null) {
        // Similar structure to lab result PDF
        // Implementation would follow same pattern
        return ['success' => true, 'message' => 'Prescription PDF generation to be implemented'];
    }
    
    /**
     * Generate medical record PDF
     */
    public function generateMedicalRecordPDF($recordId, $outputPath = null) {
        // Similar structure to lab result PDF
        // Implementation would follow same pattern
        return ['success' => true, 'message' => 'Medical record PDF generation to be implemented'];
    }
}

?>
