 <aside class="main-sidebar">
  
  <section class="sidebar">
    
    <div class="user-panel">
      <div class="pull-left info">
        <p>คุณ <?php echo $username; ?></p>

      </div>
    </div>
   
        <li>
        <a href="admin.php"><i class="fa fa-home"></i>
          <span> หน้าหลัก</span>
        </a>
      </li>
      
      
           <li class="active">
        <a href=""><i class="fa fa-cogs"></i> <span>จัดการข้อมูลระบบ</span>
        <span class="pull-right-container">
          <i class="fa fa-angle-down pull-right"></i>
        </span>
      </a>
    </li>
    
      <li>
        <a href="manage_users.php"><i class="glyphicon glyphicon-record"></i>
          <span> จัดการสมาชิก</span>
        </a>
      </li>
      <li>
        <a href="add_place_.php"><i class="glyphicon glyphicon-record"></i>
          <span> จัดการประเภท </span>
        </a>
      </li>
 
           
        <li>
        <a href="logout.php" onclick="return confirm('คุณต้องการออกจากระบบหรือไม่ ?');"><i class="glyphicon glyphicon-off"></i>
          <span> ออกจากระบบ</span>
        </a>
      </li>
    </ul>
  </section>
  <!-- /.sidebar -->
</aside>