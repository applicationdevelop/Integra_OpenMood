<?php
header("Content-type: text/html; charset=utf-8");

require_once 'logMsg.php';
require_once 'conexao.php';
require_once 'phpmailerConf.php';
require_once 'funcoes.php';

$query_view = $con->query("SELECT * FROM v_OpenMood");

while ($row = $query_view->fetch_assoc())
{
    $firstname = $row['firstname'];
    $lastname = $row['lastname'];
    $email = $row['email'];
    $username = $email;
    $password = MD5(strtolower($row['firstname']."_".date("y")));
    $nometratado = tratanome($row['firstname']);
    $passuser = $nometratado."_".date("y");
    // $confirmed = "1";
    $description = "Inscrito automaticamente pelo sistema.";
    // $mnethostid = "1";
    $nomecurso = utf8_encode($row['namecourse_open']);
    $idnumber = $row['sku_idnumber'];
    $idUsuarioMoodle = $row['userid_mood'];
    $courseid = $row['courseid_mood'];

    setlocale(LC_ALL, "pt_BR.utf8");
    $datainicio =  strftime("%d de %B");
    $datafim = strftime("%d de %B de %Y", strtotime('+32 days'));

    $timestamp_datainicio = strtotime('NOW');
    $timestamp_datafinal = strtotime('+32 days');

    $sql_linha = $con->query("SELECT email FROM moodle.mdl_user WHERE email='$email'");
    $sql_linha_result = $sql_linha->num_rows;

    if ($sql_linha_result === 0)
    {
        echo "<br>E-mail não existe. ".$email."<br /><br />";
        // // Rgistra Aluno Novo
        $result_insert = $con->query("INSERT INTO moodle.mdl_user (auth,firstname,lastname,email,username,password,confirmed,description,mnethostid,country,lang)
                                        VALUES ('manual','$firstname','$lastname','$email','$username','$password',1,'$description',1,'BR','pt_br')");

        if ($result_insert === TRUE)
        {
            echo "Usuario Registrado";


            // Recupera a chave da modalidade da matrícula do curso
            $result_courseid = $con->query("SELECT id FROM moodle.mdl_enrol WHERE courseid = '$courseid' AND enrol='manual'");
            $row = $result_courseid->fetch_assoc();
            $mdl_enrol_id = $row['id'];

            // Recupera o contexto do curso.
            $result_contextCurso = $con->query("SELECT id FROM moodle.mdl_context WHERE instanceid = '$courseid' AND contextlevel=50");
            $row_context = $result_contextCurso->fetch_assoc();
            $result_contexid = $row_context['id'];


            $last_idUser = $con->insert_id;
            // enroll_to_course($courseid,$last_idUser);

            // Inscreve o aluno na tabela mdl_user_enrolments
            $inserirAlunoCurso = $con->query("INSERT INTO moodle.mdl_user_enrolments (status,enrolid,userid,timestart,timeend,timecreated,timemodified)
                                               VALUES (0,'$mdl_enrol_id','$last_idUser','$timestamp_datainicio','$timestamp_datafinal','$timestamp_datainicio','$timestamp_datainicio')");

            // Efetua a matricula no curso
            $efetua_matricula = $con->query("INSERT INTO moodle.mdl_role_assignments (roleid,contextid,userid,timemodified)
                                               VALUES (5,'$result_contexid','$last_idUser','$timestamp_datainicio')");

            if ($inserirAlunoCurso === TRUE) {
                echo "Aluno Cadastrado no Curso";
            } else {
                echo "<br>Erro: " . $inserirAlunoCurso . " " . $con->error ;
            }

            if ($efetua_matricula === TRUE) {
                echo "Aluno Matriculado no Curso";
            } else {
                echo "<br>Erro: " . $efetua_matricula . " " . $con->error;
            }
        }
    }
    else
    {
        echo "<br />E-mail " .$email. " existe | SKU: ".$idnumber. " | ID Moodle " .$idUsuarioMoodle. " | ID Curso: ".$courseid." -> ";

        // Recupera a chave da modalidade da matrícula do curso
        $result_courseid = $con->query("SELECT id FROM moodle.mdl_enrol WHERE courseid = '$courseid' AND enrol='manual'");
        $row = $result_courseid->fetch_assoc();
        $mdl_enrol_id = $row['id'];

        // Recupera o contexto do curso.
        $result_contextCurso = $con->query("SELECT id FROM moodle.mdl_context WHERE instanceid = '$courseid' AND contextlevel=50");
        $row_context = $result_contextCurso->fetch_assoc();
        $result_contexid = $row_context['id'];

        // Verifica se o aluno está matriculado no curso.
        $verifica_inscricao = $con->query("SELECT enrolid FROM moodle.mdl_user_enrolments mmue WHERE mmue.userid = '$idUsuarioMoodle' AND mmue.enrolid = '$mdl_enrol_id' ");
        $result_verifica_inscricao = $verifica_inscricao->num_rows;

        // Verifica se o aluno concluiu o curso
        // $verifica_conclusao = $con->query("SELECT u.id, u.firstname,u.lastname, u.email,c.timecompleted FROM moodle.mdl_course_completions c INNER JOIN moodle.mdl_user u ON c.userid=u.id WHERE  c.timecompleted > 0  AND c.course = '$courseid'");
        $verifica_conclusao = $con->query("SELECT COUNT(id) AS countrecord FROM mdl_course_completions WHERE userid='$idUsuarioMoodle' AND course='$courseid' AND timecompleted > 0");
        $row_verifica_conclusao = $verifica_conclusao->fetch_assoc();
        $result_verifica_conclusao = $row_verifica_conclusao['countrecord'];

        printf($result_verifica_conclusao);

        if ($result_verifica_inscricao > 0)
        {
            echo " | Inscrição já realizada.";

            if ($result_verifica_conclusao > 0)
            {

                echo " | Curso concluído";
            }
            else
            {
                echo " | Aluno Matriculado, mas não concluiu o curso";
            }
        }
        else
        {

            echo " | Inscrição não realizada.";

            // enroll_to_course($courseid,$idUsuarioMoodle);

            // // Inscreve o aluno na tabela mdl_user_enrolments
            // $inserirAlunoCurso = $con->query("INSERT INTO moodle.mdl_user_enrolments (status,enrolid,userid,timestart,timeend,timecreated,timemodified)
            //                                    VALUES (0,'$mdl_enrol_id','$idUsuarioMoodle','$timestamp_datainicio','$timestamp_datafinal','$timestamp_datainicio','$timestamp_datainicio')");

            // // Efetua a matricula no curso
            // $efetua_matricula = $con->query("INSERT INTO moodle.mdl_role_assignments (roleid,contextid,userid,timemodified)
            //                                    VALUES (5,'$result_contexid','$idUsuarioMoodle','$timestamp_datainicio')");

            // if ($inserirAlunoCurso === TRUE) {
            //     echo "Aluno Cadastrado no Curso";
            // } else {
            //     echo "<br>Erro: " . $inserirAlunoCurso . " " . $con->error ;
            // }

            // if ($efetua_matricula === TRUE) {
            //     echo "Aluno Matriculado no Curso";
            // } else {
            //     echo "<br>Erro: " . $efetua_matricula . " " . $con->error;
            // }
        }

        // verifica se o e-mail existe e verifica se esta associado ao curso
        $sql_confereSku = $con->query("SELECT mue.userid AS useridUserEnrol from moodle.mdl_user_enrolments mue where mue.userid = '$idUsuarioMoodle' LIMIT 1");
        // $sql_confereSku = $con->query("SELECT COUNT(id) AS countrecord FROM mdl_course_completions WHERE userid='$idUsuarioMoodle' AND course='$courseid' AND timestarted = 0");
        $sql_confereSku_result = $sql_confereSku->num_rows;

        // Se trouxer 1 entra na condição e avisa ao suporte
        if ($sql_confereSku !== 0 )
        {
            echo " | Curso não acessado.";

        }
        else {
            echo "Iniciou o Curso";
        }

    }
}

mysqli_free_result($query_view);
