<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version="1.0">

  <xsl:variable name="license-locale">
    <xsl:value-of select="/answers/locale"/>
  </xsl:variable>

  <xsl:template name="attribution">
    <xsl:choose>
       <!-- generated locale choice lines -->
       
         <xsl:when test="$license-locale='af' ">Attribution</xsl:when>
       
       
         <xsl:when test="$license-locale='ca' ">Reconeixement</xsl:when>
       
       
         <xsl:when test="$license-locale='de' ">Namensnennung</xsl:when>
       
       
         <xsl:when test="$license-locale='de_AT' ">Namensnennung</xsl:when>
       
       
         <xsl:when test="$license-locale='en' ">Attribution</xsl:when>
       
       
         <xsl:when test="$license-locale='en_CA' ">Attribution</xsl:when>
       
       
         <xsl:when test="$license-locale='en_GB' ">Attribution</xsl:when>
       
       
         <xsl:when test="$license-locale='es' ">Reconocimiento</xsl:when>
       
       
         <xsl:when test="$license-locale='fi' ">Nimi mainittava</xsl:when>
       
       
         <xsl:when test="$license-locale='fr' ">Paternité</xsl:when>
       
       
         <xsl:when test="$license-locale='gl' ">Recoñecemento</xsl:when>
       
       
         <xsl:when test="$license-locale='hr' ">Imenovanje</xsl:when>
       
       
         <xsl:when test="$license-locale='it' ">Attribuzione</xsl:when>
       
       
         <xsl:when test="$license-locale='ja' ">帰属</xsl:when>
       
       
         <xsl:when test="$license-locale='nl' ">Naamsvermelding</xsl:when>
       
       
         <xsl:when test="$license-locale='pt' ">Atribuição</xsl:when>
       
       
         <xsl:when test="$license-locale='sv' ">Atribuição</xsl:when>
       
       
         <xsl:when test="$license-locale='zh_TW' ">姓名標示</xsl:when>
       
       
         <xsl:when test="$license-locale='bg' ">Признание</xsl:when>
       
       
         <xsl:when test="$license-locale='en_AU' ">Признание</xsl:when>
       
       
         <xsl:when test="$license-locale='es_AR' ">Atribución</xsl:when>
       
       
         <xsl:when test="$license-locale='es_CL' ">Atribución</xsl:when>
       
       
         <xsl:when test="$license-locale='eu' ">Atribución</xsl:when>
       
       
         <xsl:when test="$license-locale='fr_CA' ">Paternité</xsl:when>
       
       
         <xsl:when test="$license-locale='he' ">ייחוס</xsl:when>
       
       
         <xsl:when test="$license-locale='kr' ">저작자표시</xsl:when>
       
       
         <xsl:when test="$license-locale='pl' ">Uznanie autorstwa</xsl:when>
       
       
         <xsl:when test="$license-locale='st' ">Uznanie autorstwa</xsl:when>
       
       <xsl:otherwise>Attribution</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="derivatives">
    <xsl:param name="derivs"/>

    <xsl:variable name="prettystring">

      <xsl:choose>
        <xsl:when test="$derivs='n'">
          <xsl:choose>
            <!-- generated locale choice lines -->
            
              <xsl:when test="$license-locale='af' ">NoDerivs</xsl:when>
            
            
              <xsl:when test="$license-locale='ca' ">SenseObraDerivada</xsl:when>
            
            
              <xsl:when test="$license-locale='de' ">KeineBearbeitung</xsl:when>
            
            
              <xsl:when test="$license-locale='de_AT' ">KeineBearbeitung</xsl:when>
            
            
              <xsl:when test="$license-locale='en' ">NoDerivs</xsl:when>
            
            
              <xsl:when test="$license-locale='en_CA' ">NoDerivs</xsl:when>
            
            
              <xsl:when test="$license-locale='en_GB' ">NoDerivs</xsl:when>
            
            
              <xsl:when test="$license-locale='es' ">SinObraDerivada</xsl:when>
            
            
              <xsl:when test="$license-locale='fi' ">Ei jälkiperäisiä teoksia</xsl:when>
            
            
              <xsl:when test="$license-locale='fr' ">Ei jälkiperäisiä teoksia</xsl:when>
            
            
              <xsl:when test="$license-locale='gl' ">SenObraDerivada</xsl:when>
            
            
              <xsl:when test="$license-locale='hr' ">Bez prerada</xsl:when>
            
            
              <xsl:when test="$license-locale='it' ">NoOpereDerivate</xsl:when>
            
            
              <xsl:when test="$license-locale='ja' ">派生禁止</xsl:when>
            
            
              <xsl:when test="$license-locale='nl' ">GeenAfgeleideWerken</xsl:when>
            
            
              <xsl:when test="$license-locale='pt' ">Não a obras derivadas</xsl:when>
            
            
              <xsl:when test="$license-locale='sv' ">Não a obras derivadas</xsl:when>
            
            
              <xsl:when test="$license-locale='zh_TW' ">禁止改作</xsl:when>
            
            
              <xsl:when test="$license-locale='bg' ">Без производни</xsl:when>
            
            
              <xsl:when test="$license-locale='en_AU' ">Без производни</xsl:when>
            
            
              <xsl:when test="$license-locale='es_AR' ">SinDerivadas</xsl:when>
            
            
              <xsl:when test="$license-locale='es_CL' ">SinDerivadas</xsl:when>
            
            
              <xsl:when test="$license-locale='eu' ">SinDerivadas</xsl:when>
            
            
              <xsl:when test="$license-locale='fr_CA' ">SinDerivadas</xsl:when>
            
            
              <xsl:when test="$license-locale='he' ">איסור יצירות נגזרות</xsl:when>
            
            
              <xsl:when test="$license-locale='kr' ">변경금지</xsl:when>
            
            
              <xsl:when test="$license-locale='pl' ">Bez utworów zależnych</xsl:when>
            
            
              <xsl:when test="$license-locale='st' ">Bez utworów zależnych</xsl:when>
            

            <xsl:otherwise>NoDerivs</xsl:otherwise>
          </xsl:choose>
        </xsl:when>
 
        <xsl:when test="$derivs='sa'">
          <xsl:choose>
            <!-- generated locale choice lines -->
            
              <xsl:when test="$license-locale='af' ">ShareAlike</xsl:when>
            
            
              <xsl:when test="$license-locale='ca' ">CompartirIgual</xsl:when>
            
            
              <xsl:when test="$license-locale='de' ">Weitergabe unter gleichen Bedingungen</xsl:when>
            
            
              <xsl:when test="$license-locale='de_AT' ">Weitergabe unter gleichen Bedingungen</xsl:when>
            
            
              <xsl:when test="$license-locale='en' ">ShareAlike</xsl:when>
            
            
              <xsl:when test="$license-locale='en_CA' ">ShareAlike</xsl:when>
            
            
              <xsl:when test="$license-locale='en_GB' ">ShareAlike</xsl:when>
            
            
              <xsl:when test="$license-locale='es' ">CompartirIgual</xsl:when>
            
            
              <xsl:when test="$license-locale='fi' ">Sama lisenssi</xsl:when>
            
            
              <xsl:when test="$license-locale='fr' ">Sama lisenssi</xsl:when>
            
            
              <xsl:when test="$license-locale='gl' ">CompartirIgual</xsl:when>
            
            
              <xsl:when test="$license-locale='hr' ">Dijeli pod istim uvjetima</xsl:when>
            
            
              <xsl:when test="$license-locale='it' ">StessaLicenza</xsl:when>
            
            
              <xsl:when test="$license-locale='ja' ">同一条件許諾</xsl:when>
            
            
              <xsl:when test="$license-locale='nl' ">GelijkDelen</xsl:when>
            
            
              <xsl:when test="$license-locale='pt' ">Compartilhamento pela mesma licença</xsl:when>
            
            
              <xsl:when test="$license-locale='sv' ">Compartilhamento pela mesma licença</xsl:when>
            
            
              <xsl:when test="$license-locale='zh_TW' ">相同方式分享</xsl:when>
            
            
              <xsl:when test="$license-locale='bg' ">Споделяне на споделеното</xsl:when>
            
            
              <xsl:when test="$license-locale='en_AU' ">Споделяне на споделеното</xsl:when>
            
            
              <xsl:when test="$license-locale='es_AR' ">CompartirDerivadasIgual</xsl:when>
            
            
              <xsl:when test="$license-locale='es_CL' ">Licenciar Igual</xsl:when>
            
            
              <xsl:when test="$license-locale='eu' ">Licenciar Igual</xsl:when>
            
            
              <xsl:when test="$license-locale='fr_CA' ">Licenciar Igual</xsl:when>
            
            
              <xsl:when test="$license-locale='he' ">שיתוף זהה</xsl:when>
            
            
              <xsl:when test="$license-locale='kr' ">동일조건변경허락</xsl:when>
            
            
              <xsl:when test="$license-locale='pl' ">Na tych samych warunkach</xsl:when>
            
            
              <xsl:when test="$license-locale='st' ">Na tych samych warunkach</xsl:when>
            

            <xsl:otherwise>ShareAlike</xsl:otherwise>
          </xsl:choose>
        </xsl:when>

      </xsl:choose>

    </xsl:variable>

    <xsl:if test="string-length($prettystring) &gt; 0">
      <xsl:value-of select="concat('-', $prettystring)"/>
    </xsl:if>

  </xsl:template>

  <xsl:template name="noncommercial">
    <xsl:param name="commercial"/>

    <xsl:variable name="prettystring">
      <xsl:if test="./commercial='n'">
        <xsl:choose>
          <!-- generated locale choice lines -->
          
            <xsl:when test="$license-locale='af' ">NonCommercial</xsl:when>
          
          
            <xsl:when test="$license-locale='ca' ">NoComercial</xsl:when>
          
          
            <xsl:when test="$license-locale='de' ">NichtKommerziell</xsl:when>
          
          
            <xsl:when test="$license-locale='de_AT' ">NichtKommerziell</xsl:when>
          
          
            <xsl:when test="$license-locale='en' ">NonCommercial</xsl:when>
          
          
            <xsl:when test="$license-locale='en_CA' ">NonCommercial</xsl:when>
          
          
            <xsl:when test="$license-locale='en_GB' ">Non-Commercial</xsl:when>
          
          
            <xsl:when test="$license-locale='es' ">NoComercial</xsl:when>
          
          
            <xsl:when test="$license-locale='fi' ">Ei kaupalliseen käyttöön</xsl:when>
          
          
            <xsl:when test="$license-locale='fr' ">Ei kaupalliseen käyttöön</xsl:when>
          
          
            <xsl:when test="$license-locale='gl' ">NonComercial</xsl:when>
          
          
            <xsl:when test="$license-locale='hr' ">Nekomercijalno</xsl:when>
          
          
            <xsl:when test="$license-locale='it' ">NonCommerciale</xsl:when>
          
          
            <xsl:when test="$license-locale='ja' ">非営利</xsl:when>
          
          
            <xsl:when test="$license-locale='nl' ">NietCommercieel</xsl:when>
          
          
            <xsl:when test="$license-locale='pt' ">Uso Não-Comercial</xsl:when>
          
          
            <xsl:when test="$license-locale='sv' ">Uso Não-Comercial</xsl:when>
          
          
            <xsl:when test="$license-locale='zh_TW' ">非商業性</xsl:when>
          
          
            <xsl:when test="$license-locale='bg' ">Некомерсиално</xsl:when>
          
          
            <xsl:when test="$license-locale='en_AU' ">Некомерсиално</xsl:when>
          
          
            <xsl:when test="$license-locale='es_AR' ">Некомерсиално</xsl:when>
          
          
            <xsl:when test="$license-locale='es_CL' ">NoComercial</xsl:when>
          
          
            <xsl:when test="$license-locale='eu' ">NoComercial</xsl:when>
          
          
            <xsl:when test="$license-locale='fr_CA' ">NoComercial</xsl:when>
          
          
            <xsl:when test="$license-locale='he' ">שימוש לא מסחרי</xsl:when>
          
          
            <xsl:when test="$license-locale='kr' ">비영리</xsl:when>
          
          
            <xsl:when test="$license-locale='pl' ">Użycie niekomercyjne</xsl:when>
          
          
            <xsl:when test="$license-locale='st' ">Użycie niekomercyjne</xsl:when>
          
          <xsl:otherwise>NonCommercial</xsl:otherwise>
        </xsl:choose>
      </xsl:if>
    </xsl:variable>

    <xsl:if test="string-length($prettystring) &gt; 0">
      <xsl:value-of select="concat('-', $prettystring)"/>
    </xsl:if>

  </xsl:template>

</xsl:stylesheet>

