#include <stdio.h>
#include <cpgplot.h>
#include <math.h>
#include <string.h>

void draw_grid(double start_gl,double end_gl,double start_gb,double end_gb,double gstep,double bstep,int celestialCoords);
void convertXY_celestial(double raj,double decj,double *retx,double *rety);

int main(int argc,char *argv[])
{
  FILE *fin;
  int i;
  float fx[2000],fy[2000];
  double plotX[2000],plotY[2000];
  int nPlot=0;
  float mag;
  double x[2000],y[2000], px[2000],py[2000];
  double retx,rety;
  double fov=40;
  double offsetra = 0;
  double offsetdec = 0;
  char con[1000];
  char oldcon[1000]="NULL";
  float minx,maxx,miny,maxy;
  int projection=1; // 1 = xy, 2 = Aitoff
  int plotConstellation=1;
  char grDev[100]="?";

  for (i=0;i<argc;i++)
    {
      if (strcmp(argv[i],"-c")==0) // Set central position
	{
	  sscanf(argv[++i],"%lf",&offsetra);
	  sscanf(argv[++i],"%lf",&offsetdec);
	}
      if (strcmp(argv[i],"-p")==0)
	{
	  sscanf(argv[++i],"%lf",&plotX[nPlot]);
	  sscanf(argv[++i],"%lf",&plotY[nPlot++]);
	}
      if (strcmp(argv[i],"-f")==0)
	sscanf(argv[++i],"%lf",&fov);
      if (strcmp(argv[i],"-g")==0)
	sscanf(argv[++i],"%d",&projection);
      if (strcmp(argv[i],"-d")==0)
	sscanf(argv[++i],"%d",&plotConstellation);
      if (strcmp(argv[i],"-k")==0)
	sscanf(argv[++i],"%s",grDev);
    }
  cpgbeg(0,grDev,1,1);
  if (projection==1 || fov != 0.0)
    cpgpap(0,1);
  else
    cpgpap(0,0.618);
  cpgsch(1.4);
  if (fov!=0.0)
    {
      miny = offsetdec-fov/2.0;
      maxy = offsetdec+fov/2.0;
      minx = offsetra-fov/2.0;
      maxx = offsetra+fov/2.0;
      if (minx < 0) minx = 0;
      if (maxx > 360) maxx = 360;
      if (projection==2)
	{
	  convertXY_celestial(offsetra-180,offsetdec,&retx,&rety);	  
	  minx = retx-fov/2.0; miny=rety-fov/2.0;
	  maxx = retx+fov/2.0; maxy=rety+fov/2.0;
	  if (minx < -180) minx = -180;
	  if (maxx > 180) maxx = 180;

	  printf("Have %g %g %g %g\n",minx,maxx,miny,maxy);
	}
      if (miny < -90) miny = -90;
      if (maxy > 90) maxy = 90;
    }
  else
    {
      miny=-90; maxy = 90;
      if (projection==1)
	{
	  minx=0; maxx=360;
	}
      else
	{
	  minx=-180; maxx=180;
	}
    }
  if (projection==1)
    {
      cpgenv(minx,maxx,miny,maxy,0,0);
      cpglab("Right ascension (degrees)","Declination (degrees)","");
    }
  else
    {
      if (fov==0)
	draw_grid(-180,180,-90,90,60,30,1);
      else
	draw_grid(minx,maxx,miny,maxy,30,10,1);
    }
  // Read in the stars
  fin = fopen("starCoords.dat","r");
  while (!feof(fin))
    {
      if (fscanf(fin,"%f %f %f",&fx[0],&fy[0],&mag)==3)
	{
	  if (projection==1)
	    {
	      fx[0] = fx[0]*360.0/24.0;
	      fy[0] = fy[0];
	    }
	  else
	    {
	      convertXY_celestial(fx[0]*360.0/24.0-180.0,fy[0],&retx,&rety);
	      fx[0] = (float)retx;
	      fy[0] = (float)rety;
	    }
	  //	  cpgsch((4.6-mag)/3.0);
	  cpgsch(0.5*pow(5-mag,2)/8);
	  cpgpt(1,fx,fy,4); 
	}
    }
  fclose(fin);
  // Plot the pulsar positions
  for (i=0;i<nPlot;i++)
    {
      if (projection==1)
	{
	  fx[0] = (float)plotX[i];
	  fy[0] = (float)plotY[i];
	}
      else
	{
	  convertXY_celestial(plotX[i]-180.0,plotY[i],&retx,&rety);
	  fx[0] = (float)retx;
	  fy[0] = (float)rety;
	}
      cpgsci(2+i); cpgsch(2.3); 
      cpgpt(1,fx,fy,18); cpgsci(1);
    }

  // Draw the constellation boundaries
  double dx1,dy1,dx2,dy2;
  float ffx[2],ffy[2];

  fin = fopen("constellations.dat","r");
  while (!feof(fin))
    {
      if (fscanf(fin,"%s %lf %lf %lf %lf",con,&dx1,&dy1,&dx2,&dy2)==5)
	{
	  dx1=dx1/1000.0*15.0;
	  dy1=dy1/100.0;
	  dx2=dx2/1000.0*15.0;
	  dy2=dy2/100.0;
	  ffx[0] = (float)dx1;	  ffy[0] = (float)dy1;
	  ffx[1] = (float)dx2;	  ffy[1] = (float)dy2;
	  if (plotConstellation==1)
	    {
	      
	      if (strcmp(con,oldcon)!=0)
		{
		  cpgsch(0.8);
		  cpgsci(7);
		  if (projection==2)
		    {
		      convertXY_celestial(ffx[0]-180.0,ffy[0],&retx,&rety);
		      if (retx > minx && retx < maxx && rety > miny && rety < maxy)
			cpgtext((float)retx,(float)rety,con);
		    }
		  else
		    {
		      if (ffx[0] > minx && ffx[0] < maxx && ffy[0] > miny && ffy[0] < maxy)
			{		      
			  cpgtext(ffx[0],ffy[0],con);
			}
		    }
		  cpgsci(1);
		  strcpy(oldcon,con);
		  cpgsch(1.4);
		}
	    }
	  if (fabs(ffx[0]-ffx[1])<50 && fabs(ffy[0]-ffy[1])<50)
	    {
	      cpgsci(4); 
	      if (projection==2)
		{
		  convertXY_celestial(ffx[0]-180.0,ffy[0],&retx,&rety);
		  ffx[0] = (float)retx; ffy[0] = (float)rety;
		  convertXY_celestial(ffx[1]-180.0,ffy[1],&retx,&rety);
		  ffx[1] = (float)retx; ffy[1] = (float)rety;
		}

	      cpgline(2,ffx,ffy); 
	      cpgsci(1);
	    }
	}
    }
  fclose(fin);
  cpgend();
}

/*int main(int argc,char *argv[])
{
  float fx[2000],fy[2000];
  double plotX[2000],plotY[2000];
  int nPlot=0;
  double mag;
  double x[2000],y[2000], px[2000],py[2000];
  int n=0,i;
  FILE *fin;
  cpgbeg(0,"?",1,1);
  draw_grid(-180,180,-90,90,60,30,1);

  for (i=0;i<argc;i++)
    {
      if (strcmp(argv[i],"-p")==0)
	{
	  sscanf(argv[++i],"%lf",&plotX[nPlot]);
	  sscanf(argv[++i],"%lf",&plotY[nPlot++]);
	}
    }

  // Read in the stars
  fin = fopen("starCoords.dat","r");
  while (!feof(fin))
    {
      if (fscanf(fin,"%lf %lf %lf",&x[0],&y[0],&mag)==3)
	{
	  convertXY_celestial(x[0]*360.0/24.0-180.0,y[0],&px[0],&py[0]);
	  printf("Conversion = %g %g %g %g\n",x[0],y[0],px[0],py[0]);
	  fx[0] = (float)px[0];
	  fy[0] = (float)py[0];
	  //	  cpgsch((4.6-mag)/3.0);
	  cpgsch(0.5*pow(5-mag,2)/8);
	  cpgpt(1,fx,fy,4);
	}
    }
  fclose(fin);

  // Plot the pulsar positions
  for (i=0;i<nPlot;i++)
    {
      printf("Converting %g %g\n",plotX[i],plotY[i]);
      convertXY_celestial(plotX[i]-180,plotY[i],&px[0],&py[0]);
      fx[0] = (float)px[0];
      fy[0] = (float)py[0];
      cpgsci(2+i); cpgsch(2.3); 
      cpgpt(1,fx,fy,-4); cpgsci(1);
    }
  

  cpgend();
}*/
/* Convert from RAJ, DECJ to x,y using Aitoff projection */
void convertXY_celestial(double raj,double decj,double *retx,double *rety)
{
  double sa;
  double r2deg = 180.0/M_PI;
  double alpha2,delta;
  double r2,f,cgb,denom;
  double x_ret,y_ret;

  sa = raj;
  alpha2 = sa/(2*r2deg);
  delta = decj/r2deg;   

  r2 = sqrt(2.);    
  f = 2*r2/M_PI;    

  cgb = cos(delta);    
  denom =sqrt(1. + cgb*cos(alpha2));

  x_ret = cgb*sin(alpha2)*2.*r2/denom;
  y_ret = sin(delta)*r2/denom;

  x_ret = x_ret*r2deg/f;
  y_ret = y_ret*r2deg/f;

  *retx = x_ret;
  *rety = y_ret;
}

void draw_grid(double start_gl,double end_gl,double start_gb,double end_gb,double gstep,double bstep,int celestialCoords)
{
  double l,b,x,y;
  float plotx[1000],ploty[1000];
  int count=0;
  char str[100];
  if (start_gl==-180 && end_gl==180)
    cpgenv(start_gl,end_gl,start_gb,end_gb,0,-2);
  else
    cpgenv(start_gl,end_gl,start_gb,end_gb,0,-1);
  cpgsls(4);

  /* Plot lines of latitude */
  //  for (b=start_gb;b<=end_gb;b+=bstep)
  for (b=-90;b<=90;b+=bstep)
    {
      count=0;
      for (l=-180;l<=180;l=l+1.0)
	{
	  if (celestialCoords==1) convertXY_celestial(l,b,&x,&y);
	  /*get_xy(l,b,&x,&y); */
	  plotx[count] = (float)x;
	  ploty[count] = (float)y;
	  /*	  printf("%d %f %f\n",count,plotx[count],ploty[count]); */
	  count++;
	}
      cpgline(count,plotx,ploty);
    }

  /* Plot lines of longitude */
  for (l=-180;l<=180;l+=gstep)
    {
      count=0;
      for (b=-90;b<=90;b=b+1.0)
	{
	  if (celestialCoords==1) convertXY_celestial(l,b,&x,&y);
	  /*	  get_xy(l,b,&x,&y); */
	  plotx[count] = (float)x;
	  ploty[count] = (float)y;
	  count++;
	}
      if (l==-180 || l==180)
	cpgsls(1);
      else
	cpgsls(4);
      cpgline(count,plotx,ploty);
    }
  

  /* Label axes */
  cpgsci(8);
  for (l=0;l<360;l+=gstep)
    {
      if (celestialCoords==1) convertXY_celestial(l-180,-45,&x,&y);

      /*      if (celestialCoords==1)
	get_xy(l+180.0,-45,&x,&y);
      else
      get_xy(l,-45,&x,&y); */
      if (l!=180.0 || celestialCoords==1)
	{
	  if (celestialCoords==0 || l!=0)
	    {
	      if (celestialCoords==0) sprintf(str,"%.0f\\uo\\d",l);
	      else sprintf(str,"%.0f\\uh\\d",l/360.0*24.0);
	      if (x > start_gl && x < end_gl && y > start_gb && y < end_gb)
		cpgptxt((float)x,(float)y,0,0.5,str);
	    }
	}
    }
  for (b=-60;b<=60;b+=bstep)
    {
      if (celestialCoords==1) convertXY_celestial(-180,b,&x,&y);
      /*      get_xy(180,b,&x,&y); */
      if (b>0)
	{
	  sprintf(str,"+%.0f\\uo\\d",b);
	  if (x > start_gl && x < end_gl && y > start_gb && y < end_gb)
	    cpgptxt((float)x,(float)y,0,1.0,str);
	}
      else if (b==0)
	{
	  sprintf(str,"%.0f\\uo\\d",b);
	  if (x > start_gl && x < end_gl && y > start_gb && y < end_gb)
	    cpgptxt((float)x-2,(float)y,0,1.0,str);
	}
      else
	{
	  sprintf(str,"%.0f\\uo\\d",b);
	      if (x > start_gl && x < end_gl && y > start_gb && y < end_gb)
		cpgptxt((float)x,(float)y-7,0,1.0,str);
	}
    }
  cpgsci(1);
  cpgsls(1);
}

