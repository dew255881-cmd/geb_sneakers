<div class="size-guide-header">
    <div class="container">
        <h1>วิธีวัดขนาด SIZE รองเท้า</h1>
        <p>เพราะความพอดีเป็นเรื่องสำคัญที่สุดในการเลือกรองเท้า เราจึงจัดทำคู่มือการวัดไซส์มาตรฐานสากล เพื่อให้คุณมั่นใจในทุกการสั่งซื้อ</p>
    </div>
</div>

<div class="container size-guide-content">
    
    <!-- Table Men -->
    <div class="size-guide-section">
        <h2>ตารางวัดขนาดเท้าสำหรับผู้ชาย</h2>
        <div class="size-table-container">
            <table>
                <thead>
                    <tr>
                        <th>US Men</th>
                        <th>US Women</th>
                        <th>UK</th>
                        <th>EU</th>
                        <th>Length (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $menSizes = [
                        ['4', '5.5', '3.5', '36', '22'],
                        ['4.5', '6', '4', '37', '22.5'],
                        ['5', '6.5', '4.5', '37.5', '23'],
                        ['5.5', '7', '5', '38', '23.5'],
                        ['6', '7.5', '5.5', '38.5', '24'],
                        ['6.5', '8', '6', '39.5', '24.5'],
                        ['7', '8.5', '6.5', '40', '25'],
                        ['7.5', '9', '7', '40.5', '25.5'],
                        ['8', '9.5', '7.5', '41.5', '26'],
                        ['8.5', '10', '8', '42', '26.5'],
                        ['9', '10.5', '8.5', '42.5', '27'],
                        ['9.5', '11', '9', '43', '27.5'],
                        ['10', '11.5', '9.5', '44', '28'],
                        ['10.5', '12', '10', '44.5', '28.5'],
                        ['11', '12.5', '10.5', '45', '29'],
                        ['11.5', '13', '11', '45.5', '29.5'],
                        ['12', '13.5', '11.5', '46.5', '30'],
                        ['12.5', '14', '12', '47', '30.5'],
                        ['13', '15', '12.5', '47.5', '31'],
                        ['14', '–', '13.5', '49', '32'],
                        ['15', '–', '14.5', '50', '33'],
                        ['16', '–', '15.5', '51', '34'],
                        ['17', '–', '16.5', '52', '35'],
                        ['18', '–', '17.5', '53', '36'],
                        ['19', '–', '18.5', '54', '37'],
                        ['20', '–', '19.5', '55', '38'],
                    ];
                    foreach($menSizes as $row):
                    ?>
                    <tr>
                        <td><strong><?php echo $row[0]; ?></strong></td>
                        <td><?php echo $row[1]; ?></td>
                        <td><?php echo $row[2]; ?></td>
                        <td><?php echo $row[3]; ?></td>
                        <td><strong><?php echo $row[4]; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Table Women -->
    <div class="size-guide-section">
        <h2>ตารางวัดขนาดเท้าสำหรับผู้หญิง</h2>
        <div class="size-table-container">
            <table>
                <thead>
                    <tr>
                        <th>US Women</th>
                        <th>US Men</th>
                        <th>UK</th>
                        <th>EU</th>
                        <th>Length (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $womenSizes = [
                        ['4', '2.5', '2', '34', '21'],
                        ['4.5', '3', '2.5', '34.5', '21.5'],
                        ['5', '3.5', '3', '35', '22'],
                        ['5.5', '4', '3.5', '36', '22.5'],
                        ['6', '4.5', '4', '36.5', '23'],
                        ['6.5', '5', '4.5', '37', '23.5'],
                        ['7', '5.5', '5', '37.5', '24'],
                        ['7.5', '6', '5.5', '38', '24.5'],
                        ['8', '6.5', '6', '39', '25'],
                        ['8.5', '7', '6.5', '40', '25.5'],
                        ['9', '7.5', '7', '40.5', '26'],
                        ['9.5', '8', '7.5', '41', '26.5'],
                        ['10', '8.5', '8', '41.5', '27'],
                        ['10.5', '9', '8.5', '42.5', '27.5'],
                        ['11', '9.5', '9', '43', '28'],
                        ['11.5', '10', '9.5', '43.5', '28.5'],
                        ['12', '10.5', '10', '44', '29'],
                        ['12.5', '11', '10.5', '45', '29.5'],
                        ['13', '11.5', '11', '45.5', '30'],
                        ['13.5', '12', '11.5', '46', '30.5'],
                        ['14', '12.5', '12', '46.5', '31'],
                        ['15', '13', '13', '48', '32'],
                    ];
                    foreach($womenSizes as $row):
                    ?>
                    <tr>
                        <td><strong><?php echo $row[0]; ?></strong></td>
                        <td><?php echo $row[1]; ?></td>
                        <td><?php echo $row[2]; ?></td>
                        <td><?php echo $row[3]; ?></td>
                        <td><strong><?php echo $row[4]; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Width Section -->
    <div class="size-guide-section">
        <h2>การเลือกความกว้างที่เหมาะสม</h2>
        <div class="width-guide-box">
            <p>ความพอดีเป็นสิ่งสำคัญเพื่อความสบาย นั่นเป็นเหตุผลที่เราภูมิใจที่จะนำเสนอขนาดความกว้างที่หลากหลาย ตั้งแต่ X-Narrow ไปจนถึง XX-Wide</p>
            
            <h4 style="margin-top:30px; font-weight:700;">ความกว้างของรองเท้าผู้ชาย</h4>
            <div class="width-grid">
                <div class="width-card"><span class="code">2A</span><span class="label">X-Narrow</span></div>
                <div class="width-card"><span class="code">B</span><span class="label">Narrow</span></div>
                <div class="width-card"><span class="code">D</span><span class="label">Standard</span></div>
                <div class="width-card"><span class="code">2E</span><span class="label">Wide</span></div>
                <div class="width-card"><span class="code">4E</span><span class="label">X-Wide</span></div>
                <div class="width-card"><span class="code">6E</span><span class="label">XX-Wide</span></div>
            </div>

            <h4 style="margin-top:40px; font-weight:700;">ความกว้างของรองเท้าผู้หญิง</h4>
            <div class="width-grid">
                <div class="width-card"><span class="code">4A</span><span class="label">X-Narrow</span></div>
                <div class="width-card"><span class="code">2A</span><span class="label">Narrow</span></div>
                <div class="width-card"><span class="code">B</span><span class="label">Standard</span></div>
                <div class="width-card"><span class="code">D</span><span class="label">Wide</span></div>
                <div class="width-card"><span class="code">2E</span><span class="label">X-Wide</span></div>
                <div class="width-card"><span class="code">4E</span><span class="label">XX-Wide</span></div>
            </div>
        </div>
    </div>

    <!-- Tips Section -->
    <div class="size-guide-section">
        <h2>เคล็ดลับการวัดเท้าให้แม่นยำ</h2>
        <div class="pro-tips-grid">
            <div class="tip-item">
                <div class="tip-icon"><i class="fas fa-sun"></i></div>
                <div class="tip-content">
                    <strong>ลองสวมในช่วงบ่าย</strong>
                    <p>ลองสวมรองเท้าในช่วงบ่ายซึ่งเป็นช่วงที่เท้าของคุณมีขนาดใหญ่ที่สุดเนื่องจากอาการบวมตามปกติของร่างกาย</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i class="fas fa-shoe-prints"></i></div>
                <div class="tip-content">
                    <strong>ตรวจสอบความกระชับ</strong>
                    <p>ส้นเท้าควรจะกระชับพอดีโดยไม่ลื่นไถล ส่วนกลางของรองเท้าควรกระชับแต่ไม่รัดแน่น และควรมีที่ว่างให้ขยับนิ้วเท้าได้</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i class="fas fa-arrows-alt-h"></i></div>
                <div class="tip-content">
                    <strong>ถ้าอยู่ระหว่างขนาด ให้เลือกไซส์ใหญ่</strong>
                    <p>หากเท้าของคุณอยู่ระหว่างขนาด ให้เลือกไซส์ที่ใหญ่กว่าเสมอ คุณสามารถปรับให้พอดีขึ้นด้วยถุงเท้าหรือพื้นรองเท้าเสริมได้</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="tip-content">
                    <strong>วัดเท้าใหม่ทุกปี</strong>
                    <p>เท้าคนเราจะใหญ่ขึ้นตามอายุ และผู้หญิงมักจะมีขนาดเท้าใหญ่ขึ้นหลังการตั้งครรภ์ การตรวจเช็คขนาดใหม่ทุกปีจึงสำคัญมาก</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i class="fas fa-check-double"></i></div>
                <div class="tip-content">
                    <strong>ลองทั้งสองข้าง</strong>
                    <p>เท้าซ้ายและขวาอาจมีขนาดต่างกัน ควรลองรองเท้าทั้งสองข้างและเลือกซื้อตามขนาดของเท้าข้างที่ใหญ่กว่าเสมอ</p>
                </div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="tip-content">
                    <strong>อย่าเลือกรองเท้าที่เล็กเกินไป</strong>
                    <p>สัญญาณที่บอกว่ารองเท้าเล็กไปคืออาการตะคริวหรือตุ่มพอง คนส่วนใหญ่มักซื้อรองเท้าที่เล็กเกินไปโดยไม่รู้ตัว</p>
                </div>
            </div>
        </div>
    </div>

</div>
