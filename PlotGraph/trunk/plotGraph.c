// Compile using gcc -o plotGraph plotGraph.c libcpgplot.a libpgplot.a -lm -lpng -lX11 -lg2c -lz

#include <stdio.h>
#include <cpgplot.h>
#include <string.h>

int main(int argc, char *argv[])
{
  float x[1000],y[1000];
  float f[1000],t[1000];
  char out[1000]="/xs";
  float minx,maxx,miny,maxy;
  int n=0;
  int fitline=0;
  int axis=1;
  int i;
  char str[1000]="";

  for (i=0;i<argc;i++)
    {
      if (strcmp(argv[i],"-d")==0)
	{
	  sscanf(argv[++i],"%f",&f[n]);
	  sscanf(argv[++i],"%f",&t[n]);
	  n++;
	}
      else if (strcmp(argv[i],"-axis")==0)
	sscanf(argv[++i],"%d",&axis);
      else if (strcmp(argv[i],"-fit")==0)
	fitline=1;
      else if (strcmp(argv[i],"-out")==0)
	strcpy(out,argv[++i]);
    }
  
  for (i=0;i<n;i++)
    {
      if (axis==1)
	{
	  x[i] = t[i];
	  y[i] = f[i];
	}
      else if (axis==2)
	{
	  x[i] = 4149/f[i]/f[i];
	  y[i] = t[i];
	}
    }

  minx = maxx = x[0];
  miny = maxy = y[0];
  for (i=0;i<n;i++)
    {
      if (x[i] > maxx) maxx=x[i];
      if (x[i] < minx) minx=x[i];
      if (y[i] > maxy) maxy=y[i];
      if (y[i] < miny) miny=y[i];
    }
  printf("opening\n");
  //cpgbeg(0,out,1,1);
  cpgbeg(0,"/xs",1,1);
  printf("done opening\n");

  cpgscr(0,1,1,1);
  cpgscr(1,0,0,0);
  cpgsfs(2);
  /*cpgsch(1.4);
  cpgslw(4);*/
  cpgenv(minx-(maxx-minx)*0.1,maxx+(maxx-minx)*0.1,miny-(maxy-miny)*0.1,maxy+(maxy-miny)*0.1,0,0);
  cpgpt(n,x,y,9);

  if (fitline==1)
    {
      float s,sx,sy,sxx,sxy,delta,a,b;
      float fx[2],fy[2];
      s = n;
      sx = 0;
      sy = 0;
      sxx = 0;
      sxy = 0;
      for (i=0;i<n;i++)
	{
	  sx += x[i];
	  sy += y[i];
	  sxx += x[i]*x[i];
	  sxy += x[i]*y[i];
	}
      delta = s*sxx-sx*sx;
      a = (sxx*sy-sx*sxy)/delta;
      b = (s*sxy-sx*sy)/delta;
      fx[0] = minx-(maxx-minx)*0.1;
      fx[1] = maxx+(maxx-minx)*0.1;
      fy[0] = a+b*fx[0];
      fy[1] = a+b*fx[1];
      cpgsci(2); cpgline(2,fx,fy); cpgsci(1);
      if (b > 0)
	sprintf(str,"y = %.3g+%.3g x",a,b);
      else
	sprintf(str,"y = %.3g%.3g x",a,b);
      printf("%.3g\n",b);
    }
  if (axis==1)
    cpglab("Pulse arrival time, t (s)","Observing frequency, f (MHz)",str);
  else if (axis==2)
    cpglab("4149/(f\\u2\\d)","t (s)",str);

  cpgend();

}
